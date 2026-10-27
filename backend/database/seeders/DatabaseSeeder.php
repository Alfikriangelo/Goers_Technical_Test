<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an Admin user for evaluation
        User::firstOrCreate(
            ['email' => 'admin@goers.co'],
            [
                'name' => 'Goers Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Seed Restaurants and Operating Hours
        $this->call(RestaurantSeeder::class);
    }
}
