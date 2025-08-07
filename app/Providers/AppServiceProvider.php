<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // Chat messages rate limiting to prevent abuse
        RateLimiter::for('chat-messages', function (Request $request) {
            return [
                // Primary limit: 30 messages per minute for authenticated users
                Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()),

                // Burst protection: 10 messages per 10 seconds
                Limit::perMinute(10)
                    ->by($request->user()?->id ?: $request->ip())
                    ->response(function () {
                        return response()->json([
                            'success' => false,
                            'message' => 'Too many messages sent. Please slow down.',
                            'retry_after' => 10,
                            'rate_limit_exceeded' => true
                        ], 429);
                    }),
            ];
        });

        // Health check rate limiting
        RateLimiter::for('health-checks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // General API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });
    }
}
