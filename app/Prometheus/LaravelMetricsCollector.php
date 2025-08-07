<?php

namespace App\Prometheus;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Prometheus\CollectorRegistry;
use Laravel\Horizon\Contracts\MetricsRepository;

class LaravelMetricsCollector
{
    public function __construct(
        protected CollectorRegistry $registry
    ) {
    }

    public function collectMetrics(): void
    {
        $this->registerApplicationMetrics();
        $this->registerQueueMetrics();
        $this->registerPulseMetrics();
        $this->registerDatabaseMetrics();
    }

    private function registerApplicationMetrics(): void
    {
        // Memory usage gauge
        $memoryGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'app_memory_usage_bytes',
            'Application memory usage in bytes'
        );
        $memoryGauge->set(memory_get_usage(true));

        // Memory peak gauge
        $memoryPeakGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'app_memory_peak_bytes',
            'Application peak memory usage in bytes'
        );
        $memoryPeakGauge->set(memory_get_peak_usage(true));
    }

    private function registerQueueMetrics(): void
    {
        try {
            // Queue sizes for different queues
            $queues = ['default', 'broadcasts'];

            $queueSizeGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'queue_size',
                'Number of jobs in queue',
                ['queue']
            );

            foreach ($queues as $queueName) {
                try {
                    $size = Queue::size($queueName);
                    $queueSizeGauge->set($size, [$queueName]);
                } catch (\Exception $e) {
                    // If queue doesn't exist, set to 0
                    $queueSizeGauge->set(0, [$queueName]);
                }
            }

            // Failed jobs count
            try {
                $failedJobs = DB::table('failed_jobs')->count();
                $failedJobsGauge = $this->registry->getOrRegisterGauge(
                    'laravel',
                    'queue_failed_jobs_total',
                    'Total number of failed jobs'
                );
                $failedJobsGauge->set($failedJobs);
            } catch (\Exception $e) {
                // Table might not exist, set to 0
            }

            // Horizon metrics (if available)
            $this->registerHorizonMetrics();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Queue metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerHorizonMetrics(): void
    {
        try {
            if (app()->bound(MetricsRepository::class)) {
                $metrics = app(MetricsRepository::class);

                // Jobs per minute
                $jobsPerMinute = $metrics->jobsPerMinute();
                $jobsGauge = $this->registry->getOrRegisterGauge(
                    'laravel',
                    'horizon_jobs_per_minute',
                    'Jobs processed per minute by Horizon'
                );
                $jobsGauge->set($jobsPerMinute);

                // Recent jobs
                $recentJobs = $metrics->recentJobs();
                $recentJobsGauge = $this->registry->getOrRegisterGauge(
                    'laravel',
                    'horizon_recent_jobs_total',
                    'Total recent jobs processed'
                );
                $recentJobsGauge->set(count($recentJobs));
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Horizon metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerPulseMetrics(): void
    {
        try {
            $this->collectSlowQueries();
            $this->collectSlowRequests();
            $this->collectCacheStats();
            $this->collectExceptions();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Pulse metrics collection failed: ' . $e->getMessage());
        }
    }

    private function collectSlowQueries(): void
    {
        try {
            $slowQueries = DB::table('pulse_entries')
                ->where('type', 'slow_query')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();

            $slowQueriesGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_slow_queries_total',
                'Number of slow queries in last 5 minutes'
            );
            $slowQueriesGauge->set($slowQueries);
        } catch (\Exception $e) {
            // Pulse might not be set up yet, ignore
        }
    }

    private function collectSlowRequests(): void
    {
        try {
            $slowRequests = DB::table('pulse_entries')
                ->where('type', 'slow_request')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();

            $slowRequestsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_slow_requests_total',
                'Number of slow requests in last 5 minutes'
            );
            $slowRequestsGauge->set($slowRequests);

            // Request throughput
            $recentRequests = DB::table('pulse_entries')
                ->where('type', 'user_request')
                ->where('timestamp', '>=', now()->subMinute()->timestamp)
                ->count();

            $requestsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_requests_per_minute',
                'Number of requests per minute'
            );
            $requestsGauge->set($recentRequests);
        } catch (\Exception $e) {
            // Pulse might not be set up yet, ignore
        }
    }

    private function collectCacheStats(): void
    {
        try {
            // Cache hit/miss from Pulse
            $cacheHits = DB::table('pulse_entries')
                ->where('type', 'cache_hit')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();

            $cacheMisses = DB::table('pulse_entries')
                ->where('type', 'cache_miss')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();

            $cacheHitsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_cache_hits_total',
                'Cache hits in last 5 minutes'
            );
            $cacheHitsGauge->set($cacheHits);

            $cacheMissesGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_cache_misses_total',
                'Cache misses in last 5 minutes'
            );
            $cacheMissesGauge->set($cacheMisses);

            // Cache effectiveness ratio
            $total = $cacheHits + $cacheMisses;
            $effectiveness = $total > 0 ? ($cacheHits / $total) * 100 : 0;

            $effectivenessGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_cache_effectiveness_percent',
                'Cache effectiveness percentage'
            );
            $effectivenessGauge->set($effectiveness);
        } catch (\Exception $e) {
            // Pulse might not be set up yet, ignore
        }
    }

    private function collectExceptions(): void
    {
        try {
            $exceptions = DB::table('pulse_entries')
                ->where('type', 'exception')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();

            $exceptionsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_exceptions_total',
                'Number of exceptions in last 5 minutes'
            );
            $exceptionsGauge->set($exceptions);
        } catch (\Exception $e) {
            // Pulse might not be set up yet, ignore
        }
    }

    private function registerDatabaseMetrics(): void
    {
        try {
            // Simple database connection test
            $connectionsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'database_connections_active',
                'Number of active database connections'
            );

            // Test database connectivity
            DB::connection()->getPdo();
            $connectionsGauge->set(1); // At least one connection is active

        } catch (\Exception $e) {
            $connectionsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'database_connections_active',
                'Number of active database connections'
            );
            $connectionsGauge->set(0); // Database not available
        }
    }
}
