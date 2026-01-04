<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        $idTypes = ['CCCD/CMND', 'Hộ chiếu', 'Bằng lái xe', 'Khác'];
        $countries = ['Việt Nam', 'Thái Lan', 'Singapore', 'Malaysia'];
        $cities = ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Cần Thơ'];

        for ($i = 0; $i < 20; $i++) {
            DB::table('customers')->insert([
                'full_name'   => $faker->name,
                'type'        => $faker->randomElement(['Sale', 'Sale TA', 'OTA']),
                'email'       => $faker->unique()->safeEmail,
                'phone'       => $faker->phoneNumber,
                'id_type'     => $faker->randomElement($idTypes),
                'id_number'   => $faker->numerify('##########'),
                'partner_id' => DB::table('partners')->inRandomOrder()->value('id'),
                'dob'         => $faker->date('Y-m-d', '-18 years'),
                'nationality' => 'Việt Nam',
                'country'     => $faker->randomElement($countries),
                'address'     => $faker->address,
                'city'        => $faker->randomElement($cities),
            ]);
        }
    }
}
