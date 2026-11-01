<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\RestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestaurantApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RestaurantSeeder::class);
    }

    public function test_can_get_restaurant_list(): void
    {
        $response = $this->getJson('/api/restaurants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'formatted_schedule',
                        'cuisine_type',
                        'rating',
                        'is_open_now',
                        'open_days_count',
                        'shifts_per_week',
                        'weekly_hours',
                    ],
                ],
                'links',
                'meta',
            ]);

        // The API exposes structured hours only; no raw schedule string and no
        // duplicated shift rows.
        $first = $response->json('data.0');
        $this->assertArrayNotHasKey('raw_schedule', $first);
        $this->assertArrayNotHasKey('schedules', $first);
        $this->assertCount(7, $first['weekly_hours']);
    }

    public function test_can_filter_by_name(): void
    {
        $response = $this->getJson('/api/restaurants?name=Cheesecake');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('The Cheesecake Factory', $data[0]['name']);
    }

    public function test_can_filter_by_date_and_time_midnight_crossover(): void
    {
        // 2026-10-27 is a Tuesday
        // At 01:00 AM Tuesday, Naan 'N' Curry is open (Mon-Sun 11 am - 4 am)
        $response = $this->getJson('/api/restaurants?date=2026-10-27&time=01:00');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains("Naan 'N' Curry", $names);
        $this->assertContains("Marrakech Moroccan Restaurant", $names);
    }

    public function test_can_filter_by_only_time(): void
    {
        $response = $this->getJson('/api/restaurants?time=14:00');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_admin_endpoints_require_authentication(): void
    {
        $restaurant = Restaurant::first();

        $this->postJson('/api/restaurants', ['name' => 'Unauthorised Kitchen'])
            ->assertStatus(401);
        $this->putJson("/api/restaurants/{$restaurant->id}", ['name' => 'Hijacked'])
            ->assertStatus(401);
        $this->deleteJson("/api/restaurants/{$restaurant->id}")
            ->assertStatus(401);

        $this->assertDatabaseHas('restaurants', [
            'id'   => $restaurant->id,
            'name' => $restaurant->name,
        ]);
    }

    public function test_admin_can_create_new_restaurant(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'name'         => 'Goers Bistro Gourmet',
            'cuisine_type' => 'French Fusion',
            'address'      => 'Jl. Sudirman, Jakarta',
            'rating'       => 4.9,
            'description'  => 'Best bistro in town.',
            'schedules'    => [
                ['day_of_week' => 1, 'open_time' => '11:30', 'close_time' => '14:30'],
                ['day_of_week' => 1, 'open_time' => '17:00', 'close_time' => '22:00'],
                ['day_of_week' => 2, 'open_time' => '17:00', 'close_time' => '02:00'],
            ],
        ];

        $response = $this->postJson('/api/restaurants', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Goers Bistro Gourmet')
            ->assertJsonPath('data.shifts_per_week', 3)
            ->assertJsonPath('data.open_days_count', 2);

        $this->assertDatabaseHas('restaurants', [
            'name' => 'Goers Bistro Gourmet',
        ]);

        $restaurant = Restaurant::where('name', 'Goers Bistro Gourmet')->first();
        $this->assertCount(3, $restaurant->schedules);

        $overnight = $restaurant->schedules->firstWhere('close_time', '02:00:00');
        $this->assertTrue($overnight->spans_next_day);
    }

    public function test_admin_can_update_restaurant(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $restaurant = Restaurant::first();

        $response = $this->putJson("/api/restaurants/{$restaurant->id}", [
            'name'         => 'Updated Restaurant Name',
            'cuisine_type' => 'Updated Cuisine',
            'schedules'    => [
                ['day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '21:00'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Restaurant Name')
            ->assertJsonPath('data.shifts_per_week', 1);

        $this->assertDatabaseHas('restaurants', [
            'id'   => $restaurant->id,
            'name' => 'Updated Restaurant Name',
        ]);
    }

    public function test_admin_can_delete_restaurant(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $restaurant = Restaurant::first();

        $response = $this->deleteJson("/api/restaurants/{$restaurant->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('restaurants', [
            'id' => $restaurant->id,
        ]);
    }

    public function test_updating_schedules_replaces_shifts_without_duplicating(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $restaurant = Restaurant::first();

        $this->putJson("/api/restaurants/{$restaurant->id}", [
            'name'      => $restaurant->name,
            'schedules' => [
                ['day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '21:00'],
            ],
        ])->assertStatus(200);

        $this->putJson("/api/restaurants/{$restaurant->id}", [
            'name'      => $restaurant->name,
            'schedules' => [
                ['day_of_week' => 1, 'open_time' => '10:00', 'close_time' => '20:00'],
                ['day_of_week' => 2, 'open_time' => '12:00', 'close_time' => '22:00'],
            ],
        ])->assertStatus(200);

        $restaurant->refresh();

        $this->assertCount(2, $restaurant->schedules);
        $this->assertSame('10:00:00', $restaurant->schedules->first()->open_time);
    }

    public function test_updating_without_schedules_keeps_existing_shifts(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $restaurant = Restaurant::first();
        $originalCount = $restaurant->schedules()->count();

        $this->putJson("/api/restaurants/{$restaurant->id}", [
            'name' => 'Renamed Only',
        ])->assertStatus(200);

        $this->assertCount($originalCount, $restaurant->fresh()->schedules);
    }

    public function test_update_can_clear_all_schedules(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $restaurant = Restaurant::first();

        $this->assertNotEmpty($restaurant->schedules);

        $this->putJson("/api/restaurants/{$restaurant->id}", [
            'name'      => $restaurant->name,
            'schedules' => [],
        ])->assertStatus(200);

        $this->assertCount(0, $restaurant->fresh()->schedules);
    }

    public function test_zero_length_service_window_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/restaurants', [
            'name'      => 'Instant Window Cafe',
            'schedules' => [
                ['day_of_week' => 2, 'open_time' => '12:15', 'close_time' => '12:15'],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.close_time']);
    }

    public function test_schedule_text_import_is_parsed_but_never_stored(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/restaurants', [
            'name'          => 'Legacy Import Kitchen',
            'schedule_text' => 'Mon-Fri 11:30 am - 2:30 pm & 5 pm - 10 pm',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.shifts_per_week', 10);

        $restaurant = Restaurant::where('name', 'Legacy Import Kitchen')->firstOrFail();
        $this->assertCount(10, $restaurant->schedules);

        // Only structured rows exist; there is no raw column to store text in.
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('restaurants', 'raw_schedule')
        );
    }

    public function test_stats_endpoint(): void
    {
        $response = $this->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_restaurants',
                'open_now_count',
                'cuisines_count',
                'current_day',
                'current_time',
            ]);
    }

    public function test_seeded_catalog_has_realistic_weekly_variation(): void
    {
        $this->assertSame(51, Restaurant::count());
        $this->assertSame(352, \App\Models\RestaurantSchedule::count());

        // The source gives one continuous window per day group, so no day may
        // hold more than one shift after seeding.
        $daysWithMultipleShifts = \Illuminate\Support\Facades\DB::table('restaurant_schedules')
            ->selectRaw('restaurant_id, day_of_week, count(*) as shift_count')
            ->groupBy('restaurant_id', 'day_of_week')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();
        $this->assertSame(0, $daysWithMultipleShifts);

        // Weekend hours still differ from weekday hours (Osakaya: 9 pm vs 9:30 pm).
        $osakaya = Restaurant::where('name', 'Osakaya Restaurant')->with('schedules')->first();
        $this->assertSame('21:00:00', $osakaya->schedules->firstWhere('day_of_week', 1)->close_time);
        $this->assertSame('21:30:00', $osakaya->schedules->firstWhere('day_of_week', 5)->close_time);

        $closedDayCount = Restaurant::doesntHave('schedules')->count();
        $this->assertSame(0, $closedDayCount);
    }

    public function test_a_1_cafe_is_closed_on_tuesday_and_serves_lunch_and_dinner_other_days(): void
    {
        $response = $this->getJson('/api/restaurants?name=A-1%20Cafe');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        $this->assertSame('A-1 Cafe Restaurant', $data['name']);
        $this->assertSame(6, $data['shifts_per_week']);
        $this->assertSame(6, $data['open_days_count']);

        // weekly_hours is Monday-first: index 0 = Monday, 1 = Tuesday.
        $this->assertCount(1, $data['weekly_hours'][0]['shifts']);
        $this->assertSame('Lunch & dinner', $data['weekly_hours'][0]['shifts'][0]['service_label']);
        $this->assertSame('11 am – 10 pm', $data['weekly_hours'][0]['shifts'][0]['formatted_time']);
        $this->assertTrue($data['weekly_hours'][1]['is_closed']);
        $this->assertSame('Closed', $data['weekly_hours'][1]['summary']);

        $this->assertStringContainsString('Tue closed', $data['formatted_schedule']);
    }

    public function test_continuous_service_stays_open_between_lunch_and_dinner(): void
    {
        // Monday 2026-10-26; Kushi Tsuru serves one continuous 11:30 am - 9 pm
        // window, so 3:30 pm is still open (no artificial lunch break).
        $afternoon = $this->getJson('/api/restaurants?name=Kushi%20Tsuru&date=2026-10-26&time=15:30');
        $this->assertCount(1, $afternoon->json('data'));

        $lunch = $this->getJson('/api/restaurants?name=Kushi%20Tsuru&date=2026-10-26&time=13:00');
        $this->assertCount(1, $lunch->json('data'));

        $dinner = $this->getJson('/api/restaurants?name=Kushi%20Tsuru&date=2026-10-26&time=19:00');
        $this->assertCount(1, $dinner->json('data'));

        $beforeOpen = $this->getJson('/api/restaurants?name=Kushi%20Tsuru&date=2026-10-26&time=10:00');
        $this->assertSame([], $beforeOpen->json('data'));
    }

    public function test_after_midnight_service_is_still_open_in_the_early_morning(): void
    {
        // Tuesday 2026-10-27 at 03:00 — Monday's service runs to 4 am.
        $response = $this->getJson("/api/restaurants?name=Naan&date=2026-10-27&time=03:00");

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame("Naan 'N' Curry", $data[0]['name']);
        $this->assertSame('Lunch, dinner & late night', $data[0]['weekly_hours'][0]['shifts'][0]['service_label']);
        $this->assertTrue($data[0]['weekly_hours'][0]['shifts'][0]['spans_next_day']);
    }
}

