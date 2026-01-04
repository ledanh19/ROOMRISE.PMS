<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use App\Models\Menu;
use Illuminate\Support\Arr;

class AuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** Create USER **/
        $admin = User::firstOrCreate([
            'email' => 'admin@angiapms.com',
        ], [
            'name'              => 'Admin',
            'username'          => 'admin',
            'email_verified_at' => now(),
            'password'          => 'Password123',
            'phone'             => '0645978456',
        ]);

        /** Add USER'S ROLE **/
        $admin->assignRole('Admin');

        /** UserMeta **/
        $userMetaData = [
            ['meta_key' => 'address', 'meta_value' => 'T12 Hong Linh, P15, Quan 10'],
            ['meta_key' => 'zipcode', 'meta_value' => '700000'],
        ];

        foreach ($userMetaData as $meta) {
            UserMeta::updateOrCreate(
                ['user_id' => $admin->id, 'meta_key' => $meta['meta_key']],
                ['meta_value' => $meta['meta_value']]
            );
        }
    }
}
