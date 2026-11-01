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
            // LOWER() keeps the match case-insensitive on PostgreSQL (SQLite's
            // LIKE is case-insensitive by default, PostgreSQL's is not).
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']);
        }

        // 2. Filter by Cuisine
        if ($cuisine = $request->input('cuisine')) {
            $query->whereRaw('LOWER(cuisine_type) LIKE ?', ['%'.mb_strtolower($cuisine).'%']);
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

        // Clamp to a safe page size so callers cannot dump the entire table.
        $perPage = max(1, min($perPage, 100));

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
                    'address'      => $validated['address'] ?? null,
                    'cuisine_type' => $validated['cuisine_type'] ?? null,
                    'rating'       => $validated['rating'] ?? 4.5,
                    'description'  => $validated['description'] ?? null,
                ]);

                $this->syncSchedules($restaurant, $validated);

                return $restaurant->load('schedules');
            });

            return (new RestaurantResource($restaurant))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Could not save the restaurant: ' . $e->getMessage(),
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
            return response()->json(['message' => 'Restaurant not found'], 404);
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
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($restaurant, $validated) {
                $restaurant->update([
                    'name'         => $validated['name'],
                    'address'      => $validated['address'] ?? $restaurant->address,
                    'cuisine_type' => $validated['cuisine_type'] ?? $restaurant->cuisine_type,
                    'rating'       => $validated['rating'] ?? $restaurant->rating,
                    'description'  => $validated['description'] ?? $restaurant->description,
                ]);

                $this->syncSchedules($restaurant, $validated);
            });

            return (new RestaurantResource($restaurant->fresh(['schedules'])))->response();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Could not update the restaurant: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Replace a restaurant's weekly shifts.
     *
     * Structured `schedules` always win. `schedule_text` is an optional import
     * convenience: it is parsed here and immediately discarded, so the database
     * only ever contains rows. Omitting both leaves the existing shifts alone;
     * sending an empty `schedules` array clears the week.
     */
    private function syncSchedules(Restaurant $restaurant, array $validated): void
    {
        $hasStructuredKey = array_key_exists('schedules', $validated);
        $structured = $validated['schedules'] ?? null;
        $scheduleText = $validated['schedule_text'] ?? null;

        if (!$hasStructuredKey && blank($scheduleText)) {
            return;
        }

        $rows = [];

        if ($hasStructuredKey && is_array($structured)) {
            foreach ($structured as $item) {
                $rows[] = $this->normalizeShift($item);
            }
        } elseif (filled($scheduleText)) {
            $rows = $this->parserService->parse($scheduleText);
        }

        $rows = $this->deduplicateAndSort($rows);

        $restaurant->schedules()->delete();

        if ($rows !== []) {
            $restaurant->schedules()->createMany($rows);
        }
    }

    /**
     * Normalise a structured shift payload into the stored column shape.
     *
     * `spans_next_day` is derived from the clock values instead of trusted from
     * the client, so the database constraint can never be violated by a stale
     * or malicious flag.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeShift(array $item): array
    {
        $open = $this->normalizeTime((string) $item['open_time']);
        $close = $this->normalizeTime((string) $item['close_time']);

        return [
            'day_of_week'    => (int) $item['day_of_week'],
            'open_time'      => $open,
            'close_time'     => $close,
            'spans_next_day' => $close < $open,
        ];
    }

    private function normalizeTime(string $value): string
    {
        $value = trim($value);
        $format = substr_count($value, ':') === 2 ? 'H:i:s' : 'H:i';

        $carbon = Carbon::createFromFormat($format, $value);

        if ($carbon === false) {
            throw new \InvalidArgumentException("Invalid time value: {$value}");
        }

        return $carbon->format('H:i:s');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateAndSort(array $rows): array
    {
        $unique = [];

        foreach ($rows as $row) {
            $key = implode('|', [
                $row['day_of_week'],
                $row['open_time'],
                $row['close_time'],
            ]);

            $unique[$key] = $row;
        }

        $rows = array_values($unique);

        usort($rows, fn (array $a, array $b) => [
            $a['day_of_week'], $a['open_time'], $a['close_time'],
        ] <=> [
            $b['day_of_week'], $b['open_time'], $b['close_time'],
        ]);

        return $rows;
    }

    /**
     * Remove the specified restaurant from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $restaurant = Restaurant::find($id);

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        $restaurant->delete();

        return response()->json([
            'message' => 'Restaurant deleted successfully',
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
