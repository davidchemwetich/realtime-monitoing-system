<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use App\Prometheus\LaravelMetricsCollector;

Route::get('/', function () {
    return view('welcome');
});
// Add this temporarily for testing
Route::middleware('auth')->get('/test-route', function () {
    return response()->json(['message' => 'Route is working!']);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Chat routes rate limiting
Route::middleware(['auth', 'throttle:chat-messages'])->group(function () {
    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

    Route::post('/notify', [ChatController::class, 'notify'])->name('notify');
});

// Health check route rate limiting
Route::middleware('throttle:health-checks')->group(function () {
    Route::get('/health', [HealthController::class, 'check'])->name('health.check');
});
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/metrics', function (CollectorRegistry $registry) {
    try {
        // Collect all metrics
        $metricsCollector = new LaravelMetricsCollector($registry);
        $metricsCollector->collectMetrics();

        // Render metrics
        $renderer = new RenderTextFormat();
        $metrics = $registry->getMetricFamilySamples();

        $output = $renderer->render($metrics);

        return response($output, 200)
            ->header('Content-Type', RenderTextFormat::MIME_TYPE);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Metrics endpoint error: ' . $e->getMessage());

        return response('# Metrics temporarily unavailable', 200)
            ->header('Content-Type', RenderTextFormat::MIME_TYPE);
    }
});

require __DIR__ . '/auth.php';





