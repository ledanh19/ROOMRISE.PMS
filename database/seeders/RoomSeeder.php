<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        $properties = DB::table('properties')->get();

        foreach ($properties as $property) {
            for ($i = 1; $i <= 3; $i++) {
                DB::table('rooms')->insert([
                    'name' => 'Loại phòng ' . $i . ' - ' . $property->name,
                    'property_id' => $property->id,
                    'unit' => 'Phòng',
                    'quantity' => rand(2, 5),
                    'max_people' => rand(2, 6),
                    'adults' => rand(1, 4),
                    'children' => rand(0, 2),
                    'external_id' => 'RT' . $property->id . $i,
                ]);
            }
        }
    }
}
