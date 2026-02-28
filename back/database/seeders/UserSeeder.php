<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $user = User::updateOrCreate(
            ['email' => 'admin@dentalcare.com'],
            [
                'name' => 'Admin User',
                'number' => '0934567890',
                'password' => Hash::make('admin1234'),
                'verified_at' => now(),
                'profile_image' => 'https://via.placeholder.com/200x200.png/003388?text=Admin',
                'fcm_token' => '03b9f6bf-f0e5-313a-a429-c16df9839e5d',
            ]
        );

        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
