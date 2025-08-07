<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function ($app) {
            // Use InMemory storage for simplicity
            // In production, you might want to use Redis storage
            return new CollectorRegistry(new InMemory(), false);
        });
    }

    public function boot(): void
    {
        // Boot logic if needed
    }
}
