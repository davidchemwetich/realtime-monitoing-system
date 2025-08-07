<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configuring the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('chat-messages', function (Request $request) {
            return [
                // Primary limit: 30 messages per minute
                Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()),

                // Burst protection: 10 messages per 10 seconds
                Limit::perMinute(10)
                    ->by($request->user()?->id ?: $request->ip())
                    ->response(function () {
                        return response()->json([
                            'success' => false,
                            'message' => 'Too many messages sent. Please slow down.',
                            'retry_after' => 10
                        ], 429);
                    }),
            ];
        });

        RateLimiter::for('health-checks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
