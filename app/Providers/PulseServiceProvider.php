<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function ($user = null) {
            // In local environment, allow all authenticated users
            if (app()->environment('local')) {
                return $user !== null;
            }

            // In production, only allow specific users
            return $user && in_array($user->email, [
                'dchemwetich@yahoo.com',
            ]);
        });
    }
}