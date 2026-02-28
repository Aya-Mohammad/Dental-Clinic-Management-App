<?php

namespace App\Providers;
use App\Services\FirebaseService;

use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FirebaseService::class, function ($app) {
            return new FirebaseService();
        });
    }

    public function boot()
    {
        //
    }
}
