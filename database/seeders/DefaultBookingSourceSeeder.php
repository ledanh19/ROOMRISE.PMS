<?php

namespace Database\Seeders;

use App\Models\BookingSource;
use Illuminate\Database\Seeder;

class DefaultBookingSourceSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSources = [
            'Airbnb',
            'Agoda',
            'Booking.com',
            'Expedia',
            'Ctrip',
            'Trip',
            'Klook',
            'Traveloka'
        ];

        foreach ($defaultSources as $sourceName) {
            BookingSource::firstOrCreate(
                ['name' => $sourceName],
                [
                    'name' => $sourceName,
                    'is_default' => true,
                    'price_percentage' => 0.00
                ]
            );
        }
    }
}
