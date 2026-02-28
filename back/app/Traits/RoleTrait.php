<?php

namespace App\Traits;
use App\Models\Role;

trait RoleTrait
{
    public function hasRole($name)
    {
        return $this->roles()->where('name', $name)->exists();
    }

    public function addRoleByName($name)
    {
        $role = Role::where('name', $name)->first();
        if ($role && !$this->hasRole($name)) {
            $this->roles()->attach($role);
        }
    }

    public function updateMainRole($newName)
    {
        $role = Role::where('name', $newName)->first();
        if ($role) {
            $this->roles()->sync([$role->id]);
        }
    }
}

