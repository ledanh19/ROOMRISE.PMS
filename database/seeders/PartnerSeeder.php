<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        $types = ['Sale', 'Sale TA'];
        $paymentMethods = ['Công nợ'];
        $statuses = ['Hoạt động', 'Không hoạt động'];

        $existingCodes = [];

        for ($i = 0; $i < 10; $i++) {
            do {
                $code = strtoupper(Str::random(6));
            } while (in_array($code, $existingCodes));

            $existingCodes[] = $code;

            DB::table('partners')->insert([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'type' => $faker->randomElement($types),
                'commission' => $faker->randomFloat(2, 5, 20),
                'payment_method' => $faker->randomElement($paymentMethods),
                'internal_code' => $code,
                'status' => $faker->randomElement($statuses),
                'address' => $faker->address,
                'city' => $faker->city,
                'country' => $faker->country,
                'internal_note' => $faker->sentence,
            ]);
        }
    }
}
