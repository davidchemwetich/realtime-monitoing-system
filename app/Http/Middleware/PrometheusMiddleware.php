<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;

class PrometheusMiddleware
{
    protected $registry;
    protected $counter;
    protected $histogram;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        try {
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            $labels = [
                $request->method(),
                $request->route()?->getName() ?? 'unknown',
                (string) $response->status()
            ];

            // Register counter if not exists
            if (!$this->counter) {
                $this->counter = $this->registry->getOrRegisterCounter(
                    'laravel',
                    'http_requests_total',
                    'Total HTTP requests',
                    ['method', 'route', 'status_code']
                );
            }

            // Register histogram if not exists
            if (!$this->histogram) {
                $this->histogram = $this->registry->getOrRegisterHistogram(
                    'laravel',
                    'http_request_duration_ms',
                    'HTTP request duration in milliseconds',
                    ['method', 'route', 'status_code'],
                    [50, 100, 200, 500, 1000, 2000, 5000]
                );
            }

            // Record metrics
            $this->counter->inc($labels);
            $this->histogram->observe($duration, $labels);

        } catch (\Exception $e) {
            // Log error but don't break the response
            \Illuminate\Support\Facades\Log::warning('Prometheus middleware error: ' . $e->getMessage());
        }

        return $response;
    }
}
