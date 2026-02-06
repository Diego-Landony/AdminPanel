<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates test drivers for restaurant ID 1 if it exists:
     * - One active and available driver
     * - One active but unavailable driver
     * - One inactive driver
     */
    public function run(): void
    {
        $restaurant = Restaurant::find(1);

        if (! $restaurant) {
            $this->command->warn('Restaurant ID 1 not found. Skipping driver seeding.');

            return;
        }

        // Driver 1: Active and available with location
        Driver::factory()
            ->available()
            ->withLocation()
            ->create([
                'restaurant_id' => $restaurant->id,
                'name' => 'Carlos Rodriguez',
                'email' => 'carlos.driver@test.com',
                'phone' => '+502 5555 1001',
            ]);

        // Driver 2: Active but unavailable (offline)
        Driver::factory()
            ->unavailable()
            ->create([
                'restaurant_id' => $restaurant->id,
                'name' => 'Maria Lopez',
                'email' => 'maria.driver@test.com',
                'phone' => '+502 5555 1002',
            ]);

        // Driver 3: Inactive
        Driver::factory()
            ->inactive()
            ->create([
                'restaurant_id' => $restaurant->id,
                'name' => 'Juan Perez',
                'email' => 'juan.driver@test.com',
                'phone' => '+502 5555 1003',
            ]);

        $this->command->info('Created 3 test drivers for restaurant ID 1.');
    }
}
