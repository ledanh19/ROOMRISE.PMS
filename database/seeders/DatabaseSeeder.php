<?php

namespace Database\Seeders;javascript:;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            // RoleSeeder::class,
            // MenuSeeder::class,
            // AuthSeeder::class,
            // PartnerSeeder::class,
            // CustomerSeeder::class,
            // PropertySeeder::class,
            // RoomSeeder::class,
            // RoomUnitSeeder::class,
            // BookingSeeder::class,
            DefaultBookingSourceSeeder::class,
        ]);
    }
}
