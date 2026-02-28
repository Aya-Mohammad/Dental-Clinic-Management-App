<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\User::class => \App\Policies\PatientPolicy::class,
        \App\Models\Treatment::class => \App\Policies\TreatmentPolicy::class,
        \App\Models\Appointment::class => \App\Policies\AppointmentPolicy::class,
        \App\Models\MedicalRecord::class => \App\Policies\MedicalRecordPolicy::class
    ];

    public function boot()
    {
        Gate::define('is-admin', fn($user) => $user->role === 'A');
        Gate::define('is-manager', fn($user) => $user->role === 'M');
        Gate::define('is-doctor', fn($user) => $user->role === 'D');
        Gate::define('is-secretary', fn($user) => $user->role === 'S');
        Gate::define('is-patient', fn($user) => $user->role === 'P');
    }
}
