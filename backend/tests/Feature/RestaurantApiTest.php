<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\RestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                        'raw_schedule',
                        'formatted_schedule',
                        'cuisine_type',
                        'rating',
                        'is_open_now',
                    ],
                ],
                'links',
                'meta',
            ]);
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

    public function test_admin_can_create_new_restaurant(): void
    {
        $payload = [
            'name'         => 'Goers Bistro Gourmet',
            'raw_schedule' => 'Mon-Fri 10 am - 10 pm / Sat-Sun 11 am - 11 pm',
            'cuisine_type' => 'French Fusion',
            'address'      => 'Jl. Sudirman, Jakarta',
            'rating'       => 4.9,
            'description'  => 'Best bistro in town.',
        ];

        $response = $this->postJson('/api/restaurants', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Goers Bistro Gourmet');

        $this->assertDatabaseHas('restaurants', [
            'name' => 'Goers Bistro Gourmet',
        ]);

        // Verify schedules were generated
        $restaurant = Restaurant::where('name', 'Goers Bistro Gourmet')->first();
        $this->assertCount(7, $restaurant->schedules);
    }

    public function test_admin_can_update_restaurant(): void
    {
        $restaurant = Restaurant::first();

        $response = $this->putJson("/api/restaurants/{$restaurant->id}", [
            'name'         => 'Updated Restaurant Name',
            'raw_schedule' => 'Mon-Sun 9 am - 9 pm',
            'cuisine_type' => 'Updated Cuisine',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Restaurant Name');

        $this->assertDatabaseHas('restaurants', [
            'id'   => $restaurant->id,
            'name' => 'Updated Restaurant Name',
        ]);
    }

    public function test_admin_can_delete_restaurant(): void
    {
        $restaurant = Restaurant::first();

        $response = $this->deleteJson("/api/restaurants/{$restaurant->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('restaurants', [
            'id' => $restaurant->id,
        ]);
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
}
