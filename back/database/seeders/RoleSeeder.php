<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = ['admin', 'manager', 'doctor', 'secretary', 'patient'];

        foreach ($roles as $role) Role::updateOrCreate( ['name' => $role] );
    }
}
