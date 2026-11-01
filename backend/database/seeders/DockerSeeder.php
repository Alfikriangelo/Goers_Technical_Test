<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Container entrypoint seeder.
 *
 * `DatabaseSeeder` is destructive (it truncates the restaurant tables), so it
 * must never run on every container start. This seeder only fills an empty
 * database and always guarantees the admin account exists.
 */
class DockerSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@goers.co'],
            [
                'name'     => 'Goers Admin',
                'password' => Hash::make('password'),
            ]
        );

        if (Restaurant::query()->exists()) {
            $this->command?->info('Restaurants already present — skipping dataset seed.');

            return;
        }

        $this->call(RestaurantSeeder::class);
    }
}
