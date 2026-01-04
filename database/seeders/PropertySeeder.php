<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        $types = ['Khách sạn', 'Homestay', 'Villa', 'Resort'];
        $categories = ['Cơ bản', 'Trung cấp', 'Cao cấp'];
        $sources = ['Direct', 'Agency', 'OTA'];
        $currencies = ['VND', 'USD'];
        $websites = ['https://example.com', 'https://staynow.vn', 'https://hotelhub.vn'];

        for ($i = 0; $i < 3; $i++) {
            DB::table('properties')->insert([
                'name' => 'Property ' . ($i + 1),
                'type' => $faker->randomElement($types),
                'city' => $faker->city,
                'address' => $faker->address,
                'country' => 'Việt Nam',
                'phone' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'source' => $faker->randomElement($sources),
                'drop_code' => strtoupper(Str::random(6)),
                'key' => strtoupper(Str::random(8)),
                'max_room_types' => rand(2, 10),
                'max_rooms' => rand(10, 50),
                'owner_id' => 1,
                'external_id' => 'PROP' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'currency' => $faker->randomElement($currencies),
                'is_active' => true,
                'is_sync_enabled' => $faker->boolean(80),
                'webhook_id' => null,
                'website' => $faker->randomElement($websites),
                'category' => $faker->randomElement($categories),
                'max_count_of_rate_plans' => rand(1, 5),
            ]);
        }
    }
}
