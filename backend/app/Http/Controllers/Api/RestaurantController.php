<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\ScheduleParserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RestaurantController extends Controller
{
    public function __construct(
        protected ScheduleParserService $parserService
    ) {}

    /**
     * Display a listing of restaurants with optional search & datetime filters.
     *
     * @queryParam name string Filter by restaurant name (case-insensitive partial match).
     * @queryParam date string Filter by date in YYYY-MM-DD format.
     * @queryParam time string Filter by time in HH:mm or HH:mm:ss format.
     * @queryParam open_now boolean If true, filters restaurants currently open right now.
     * @queryParam cuisine string Filter by cuisine type.
     * @queryParam per_page integer Number of items per page (default: 12, -1 for all).
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = Restaurant::query()->with('schedules');

        // 1. Filter by Name (Search)
        if ($search = $request->input('name') ?? $request->input('q') ?? $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // 2. Filter by Cuisine
        if ($cuisine = $request->input('cuisine')) {
            $query->where('cuisine_type', 'like', "%{$cuisine}%");
        }

        // 3. Filter by Date and/or Time
        $dateInput = $request->input('date');
        $timeInput = $request->input('time');
        $openNow = filter_var($request->input('open_now'), FILTER_VALIDATE_BOOLEAN);

        if ($openNow) {
            $now = Carbon::now();
            $dayOfWeek = (int) $now->isoFormat('E');
            $timeString = $now->format('H:i:s');
            $query->openAt($dayOfWeek, $timeString);
        } elseif ($dateInput && $timeInput) {
            // Both date and time provided
            $date = Carbon::parse($dateInput);
            $dayOfWeek = (int) $date->isoFormat('E'); // 1 (Mon) - 7 (Sun)
            $timeString = Carbon::parse($timeInput)->format('H:i:s');

            $query->openAt($dayOfWeek, $timeString);
        } elseif ($dateInput) {
            // Only date provided -> restaurants open on that day of week
            $date = Carbon::parse($dateInput);
            $dayOfWeek = (int) $date->isoFormat('E');

            $query->openOnDay($dayOfWeek);
        } elseif ($timeInput) {
            // Only time provided -> restaurants open at that time today
            $today = Carbon::now();
            $dayOfWeek = (int) $today->isoFormat('E');
            $timeString = Carbon::parse($timeInput)->format('H:i:s');

            $query->openAt($dayOfWeek, $timeString);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        if (in_array($sortBy, ['name', 'rating', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        $perPage = (int) $request->input('per_page', 12);

        if ($perPage === -1) {
            $restaurants = $query->get();
            return RestaurantResource::collection($restaurants);
        }

        $restaurants = $query->paginate($perPage);

        return RestaurantResource::collection($restaurants)->additional([
            'meta' => [
                'applied_filters' => [
                    'name'     => $search,
                    'date'     => $dateInput,
                    'time'     => $timeInput,
                    'open_now' => $openNow,
                    'cuisine'  => $cuisine,
                ],
            ],
        ]);
    }

    /**
     * Store a newly created restaurant with schedules.
     */
    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $restaurant = DB::transaction(function () use ($validated) {
                $restaurant = Restaurant::create([
                    'name'         => $validated['name'],
                    'raw_schedule' => $validated['raw_schedule'] ?? null,
                    'address'      => $validated['address'] ?? null,
                    'cuisine_type' => $validated['cuisine_type'] ?? null,
                    'rating'       => $validated['rating'] ?? 4.5,
                    'description'  => $validated['description'] ?? null,
                ]);

                // 1. If raw_schedule provided, parse it
                if (!empty($validated['raw_schedule'])) {
                    $parsedShifts = $this->parserService->parse($validated['raw_schedule']);
                    foreach ($parsedShifts as $shift) {
                        $restaurant->schedules()->create($shift);
                    }
                }

                // 2. If structured schedules array provided directly
                if (!empty($validated['schedules']) && is_array($validated['schedules'])) {
                    foreach ($validated['schedules'] as $item) {
                        $open = Carbon::parse($item['open_time'])->format('H:i:s');
                        $close = Carbon::parse($item['close_time'])->format('H:i:s');
                        $spans = isset($item['spans_next_day']) 
                            ? (bool) $item['spans_next_day'] 
                            : ($close <= $open && $close !== $open);

                        $restaurant->schedules()->create([
                            'day_of_week'    => (int) $item['day_of_week'],
                            'open_time'      => $open,
                            'close_time'     => $close,
                            'spans_next_day' => $spans,
                        ]);
                    }
                }

                return $restaurant->load('schedules');
            });

            return (new RestaurantResource($restaurant))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data restoran: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified restaurant.
     */
    public function show(int $id): JsonResponse
    {
        $restaurant = Restaurant::with('schedules')->find($id);

        if (!$restaurant) {
            return response()->json(['message' => 'Restoran tidak ditemukan'], 404);
        }

        return (new RestaurantResource($restaurant))->response();
    }

    /**
     * Update the specified restaurant in storage.
     */
    public function update(StoreRestaurantRequest $request, int $id): JsonResponse
    {
        $restaurant = Restaurant::find($id);

        if (!$restaurant) {
            return response()->json(['message' => 'Restoran tidak ditemukan'], 404);
        }

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($restaurant, $validated) {
                $restaurant->update([
                    'name'         => $validated['name'],
                    'raw_schedule' => $validated['raw_schedule'] ?? $restaurant->raw_schedule,
                    'address'      => $validated['address'] ?? $restaurant->address,
                    'cuisine_type' => $validated['cuisine_type'] ?? $restaurant->cuisine_type,
                    'rating'       => $validated['rating'] ?? $restaurant->rating,
                    'description'  => $validated['description'] ?? $restaurant->description,
                ]);

                if (!empty($validated['raw_schedule'])) {
                    $restaurant->schedules()->delete();
                    $parsedShifts = $this->parserService->parse($validated['raw_schedule']);
                    foreach ($parsedShifts as $shift) {
                        $restaurant->schedules()->create($shift);
                    }
                }
            });

            return (new RestaurantResource($restaurant->fresh(['schedules'])))->response();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui restoran: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified restaurant from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $restaurant = Restaurant::find($id);

        if (!$restaurant) {
            return response()->json(['message' => 'Restoran tidak ditemukan'], 404);
        }

        $restaurant->delete();

        return response()->json([
            'message' => 'Restoran berhasil dihapus',
        ]);
    }

    /**
     * Get aggregate statistics (for admin dashboard / header stats).
     */
    public function stats(): JsonResponse
    {
        $now = Carbon::now();
        $dayOfWeek = (int) $now->isoFormat('E');
        $timeString = $now->format('H:i:s');

        $total = Restaurant::count();
        $openNow = Restaurant::openAt($dayOfWeek, $timeString)->count();
        $cuisinesCount = Restaurant::distinct('cuisine_type')->count('cuisine_type');

        return response()->json([
            'total_restaurants' => $total,
            'open_now_count'    => $openNow,
            'cuisines_count'    => $cuisinesCount,
            'current_day'       => $now->isoFormat('dddd'),
            'current_time'      => $now->format('H:i'),
        ]);
    }
}
