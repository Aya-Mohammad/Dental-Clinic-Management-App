<?php

namespace App\Permissions;
use App\Models\User; 

final class Abilities
{
    public const ADMIN = 'admin';
    public const MANAGER = 'manager';
    public const DOCTOR = 'doctor';
    public const SECRETARY = 'secretary';
    public const PATIENT = 'patient';

    public static function getAbilities(User $user)
    {
        return $user->roles->pluck('name')->toArray();
    }
}
