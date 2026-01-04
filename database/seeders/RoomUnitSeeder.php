<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class RoomUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        $roomTypes = DB::table('rooms')->get();

        foreach ($roomTypes as $roomType) {
            for ($i = 1; $i <= 2; $i++) {
                DB::table('room_units')->insert([
                    'room_id' => $roomType->id,
                    'name' => 'Phòng ' . $i . ' - ' . $roomType->name,
                    'status' => $faker->randomElement(['available', 'occupied', 'maintenance']),
                    'note' => 'Tự động tạo seeder',
                ]);
            }
        }
    }
}
