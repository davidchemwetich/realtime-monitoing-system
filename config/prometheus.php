<?php

return [
    'enabled' => true,

    'urls' => [
        'default' => 'metrics',
    ],

    'allowed_ips' => [
        // Allow Prometheus container to access metrics
        // In Docker,as they're on same network
    ],

    'default_namespace' => 'laravel',

    'middleware' => [
        Spatie\Prometheus\Http\Middleware\AllowIps::class,
    ],

    'collectors' => [
        \App\Prometheus\LaravelMetricsCollector::class,
    ],

    'actions' => [
        'render_collectors' => Spatie\Prometheus\Actions\RenderCollectorsAction::class,
    ],

    'wipe_storage_after_rendering' => false,

    'cache' => 'array',
];
