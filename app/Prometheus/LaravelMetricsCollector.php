<?php

namespace App\Prometheus;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Pulse\Facades\Pulse;
use Prometheus\CollectorRegistry;
use Throwable;

class LaravelMetricsCollector
{
    protected CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function collectMetrics(): void
    {
        $this->registerApplicationMetrics();
        $this->registerQueueMetrics();
        $this->registerPulseMetrics();
        $this->registerDatabaseMetrics();
        $this->registerHealthMetrics();
        $this->registerHorizonMetrics();
        $this->registerCacheMetrics();
        $this->registerRateLimitingMetrics();
    }

    private function registerApplicationMetrics(): void
    {
        try {
            // Memory usage gauge
            $memoryGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'app_memory_usage_bytes',
                'Application memory usage in bytes'
            );
            $memoryGauge->set(memory_get_usage(true));

            // Peak memory usage gauge
            $memoryPeakGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'app_memory_peak_bytes',
                'Peak memory usage in bytes'
            );
            $memoryPeakGauge->set(memory_get_peak_usage(true));

            // PHP version gauge with label
            $phpVersionGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'app_php_version_info',
                'PHP version information',
                ['version']
            );
            $phpVersionGauge->set(1, [PHP_VERSION]);

            // Application uptime (approximated)
            $uptimeGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'app_uptime_seconds',
                'Application uptime in seconds'
            );
            $uptimeGauge->set($this->getApplicationUptime());

        } catch (Throwable $e) {
            Log::warning('Application metrics collection failed: ' . $e->getMessage());
        }
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
                } catch (Throwable $e) {
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
            } catch (Throwable $e) {
                // Table might not exist, set to 0
            }

        } catch (Throwable $e) {
            Log::warning('Queue metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerPulseMetrics(): void
    {
        try {
            // Get Pulse database connection
            $pulseDB = DB::connection(config('pulse.database.connection', 'mysql'));

            // Slow queries monitoring
            $slowQueries = $pulseDB->table('pulse_entries')
                ->where('type', 'slow_query')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();
            $gaugeSlowQueries = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_slow_queries_total',
                'Number of slow queries in last 5 minutes'
            );
            $gaugeSlowQueries->set($slowQueries);

            // Slow requests monitoring
            $slowRequests = $pulseDB->table('pulse_entries')
                ->where('type', 'slow_request')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();
            $gaugeSlowRequests = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_slow_requests_total',
                'Number of slow requests in last 5 minutes'
            );
            $gaugeSlowRequests->set($slowRequests);

            // Request throughput
            $recentRequests = $pulseDB->table('pulse_entries')
                ->where('type', 'user_request')
                ->where('timestamp', '>=', now()->subMinute()->timestamp)
                ->count();
            $gaugeRequests = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_requests_per_minute',
                'Number of requests per minute'
            );
            $gaugeRequests->set($recentRequests);

            // Cache hits and misses
            $cacheHits = $pulseDB->table('pulse_entries')
                ->where('type', 'cache_hit')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();
            $cacheMisses = $pulseDB->table('pulse_entries')
                ->where('type', 'cache_miss')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();

            $gaugeCacheHits = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_cache_hits_total',
                'Cache hits in last 5 minutes'
            );
            $gaugeCacheHits->set($cacheHits);

            $gaugeCacheMisses = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_cache_misses_total',
                'Cache misses in last 5 minutes'
            );
            $gaugeCacheMisses->set($cacheMisses);

            // Cache effectiveness ratio
            $total = $cacheHits + $cacheMisses;
            $cacheEffectiveness = $total > 0 ? ($cacheHits / $total) * 100 : 0;
            $gaugeCacheEff = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_cache_effectiveness_percent',
                'Cache effectiveness percentage'
            );
            $gaugeCacheEff->set($cacheEffectiveness);

            // Exceptions monitoring
            $exceptions = $pulseDB->table('pulse_entries')
                ->where('type', 'exception')
                ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                ->count();
            $gaugeExceptions = $this->registry->getOrRegisterGauge(
                'laravel',
                'pulse_exceptions_total',
                'Number of exceptions in last 5 minutes'
            );
            $gaugeExceptions->set($exceptions);

        } catch (Throwable $e) {
            Log::warning('Pulse metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerDatabaseMetrics(): void
    {
        try {
            // Database connectivity check
            $connectionsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'database_connections_active',
                'Number of active database connections'
            );

            try {
                // Try to get actual connection count
                $connections = DB::table('information_schema.processlist')
                    ->where('db', config('database.connections.mysql.database'))
                    ->count();
                $connectionsGauge->set($connections);
            } catch (Throwable $e) {
                // Fallback: if we can connect at all, set to 1
                try {
                    DB::connection()->getPdo();
                    $connectionsGauge->set(1);
                } catch (Throwable $e2) {
                    $connectionsGauge->set(0);
                }
            }

            // Database response time
            $startTime = microtime(true);
            try {
                DB::selectOne('SELECT 1');
                $responseTime = (microtime(true) - $startTime) * 1000;
                $dbResponseTimeGauge = $this->registry->getOrRegisterGauge(
                    'laravel',
                    'database_response_time_ms',
                    'Database response time in milliseconds'
                );
                $dbResponseTimeGauge->set($responseTime);
            } catch (Throwable $e) {
                // Database unreachable
            }

        } catch (Throwable $e) {
            Log::warning('Database metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerHealthMetrics(): void
    {
        try {
            // Overall application health status
            $healthGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'app_health_status',
                'Overall application health status (1=healthy, 0=unhealthy)'
            );

            // Perform comprehensive health check
            $isHealthy = $this->performComprehensiveHealthCheck();
            $healthGauge->set($isHealthy ? 1 : 0);

            // Individual service health checks
            $this->registerIndividualHealthMetrics();

            // Health check processing time
            $healthCheckTime = $this->measureHealthCheckTime();
            $healthTimingGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'health_check_duration_ms',
                'Health check processing time in milliseconds'
            );
            $healthTimingGauge->set($healthCheckTime);

        } catch (Throwable $e) {
            Log::warning('Health metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerIndividualHealthMetrics(): void
    {
        // Database health
        $dbHealthGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'database_health_status',
            'Database health status (1=healthy, 0=unhealthy)'
        );
        try {
            DB::connection()->getPdo();
            $result = DB::selectOne('SELECT 1 as test');
            $dbHealthGauge->set(($result && $result->test === 1) ? 1 : 0);
        } catch (Throwable $e) {
            $dbHealthGauge->set(0);
        }

        // Cache health
        $cacheHealthGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'cache_health_status',
            'Cache system health status (1=healthy, 0=unhealthy)'
        );
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_' . time();
            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            $cacheHealthGauge->set(($retrieved === $testValue) ? 1 : 0);
        } catch (Throwable $e) {
            $cacheHealthGauge->set(0);
        }

        // Queue health
        $queueHealthGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'queue_health_status',
            'Queue system health status (1=healthy, 0=unhealthy)'
        );
        try {
            Queue::size('default');
            $queueHealthGauge->set(1);
        } catch (Throwable $e) {
            $queueHealthGauge->set(0);
        }

        // Redis health (if configured)
        if (config('database.redis.default')) {
            $redisHealthGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'redis_health_status',
                'Redis health status (1=healthy, 0=unhealthy)'
            );
            try {
                $redis = Redis::connection();
                $redis->ping();
                $redisHealthGauge->set(1);
            } catch (Throwable $e) {
                $redisHealthGauge->set(0);
            }
        }
    }

    private function registerHorizonMetrics(): void
    {
        try {
            if (!class_exists(\Laravel\Horizon\Horizon::class)) {
                return;
            }

            // Horizon supervisor status
            if (app()->bound(MasterSupervisorRepository::class)) {
                $supervisors = app(MasterSupervisorRepository::class)->all();
                $activeSupervisors = count($supervisors);

                $horizonSupervisorGauge = $this->registry->getOrRegisterGauge(
                    'laravel',
                    'horizon_supervisors_active',
                    'Number of active Horizon supervisors'
                );
                $horizonSupervisorGauge->set($activeSupervisors);

                // Horizon health
                $horizonHealthGauge = $this->registry->getOrRegisterGauge(
                    'laravel',
                    'horizon_health_status',
                    'Horizon health status (1=healthy, 0=unhealthy)'
                );
                $horizonHealthGauge->set($activeSupervisors > 0 ? 1 : 0);
            }

        } catch (Throwable $e) {
            Log::warning('Horizon metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerCacheMetrics(): void
    {
        try {
            // Cache operations metrics
            $cacheOperationsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'cache_operations_total',
                'Total cache operations'
            );
            $cacheOperationsGauge->set(1); // Placeholder - would need actual tracking

            // Redis-specific metrics if available
            if (config('cache.default') === 'redis') {
                try {
                    $redis = Cache::getRedis();
                    $info = $redis->info();

                    if (isset($info['used_memory'])) {
                        $redisMemoryGauge = $this->registry->getOrRegisterGauge(
                            'laravel',
                            'redis_memory_used_bytes',
                            'Redis memory usage in bytes'
                        );
                        $redisMemoryGauge->set($info['used_memory']);
                    }

                    if (isset($info['connected_clients'])) {
                        $redisClientsGauge = $this->registry->getOrRegisterGauge(
                            'laravel',
                            'redis_connected_clients',
                            'Number of connected Redis clients'
                        );
                        $redisClientsGauge->set($info['connected_clients']);
                    }
                } catch (Throwable $e) {
                    // Redis info not available
                }
            }

        } catch (Throwable $e) {
            Log::warning('Cache metrics collection failed: ' . $e->getMessage());
        }
    }

    private function registerRateLimitingMetrics(): void
    {
        try {
            // Rate limiting metrics (from Pulse if available)
            $rateLimitHitsGauge = $this->registry->getOrRegisterGauge(
                'laravel',
                'rate_limit_hits_total',
                'Total rate limit hits'
            );

            // This would need to be tracked in your ChatController
            // For now, we'll set a placeholder
            try {
                $pulseDB = DB::connection(config('pulse.database.connection', 'mysql'));
                $rateLimitHits = $pulseDB->table('pulse_entries')
                    ->where('type', 'chat_rate_limits')
                    ->where('timestamp', '>=', now()->subMinutes(5)->timestamp)
                    ->sum('value');
                $rateLimitHitsGauge->set($rateLimitHits ?: 0);
            } catch (Throwable $e) {
                $rateLimitHitsGauge->set(0);
            }

        } catch (Throwable $e) {
            Log::warning('Rate limiting metrics collection failed: ' . $e->getMessage());
        }
    }

    private function performComprehensiveHealthCheck(): bool
    {
        try {
            // Check critical systems
            DB::connection()->getPdo();
            DB::selectOne('SELECT 1');

            // Check cache
            $testKey = 'health_quick_' . time();
            Cache::put($testKey, 'test', 5);
            $cacheResult = Cache::get($testKey);
            Cache::forget($testKey);

            // Check queue
            Queue::size('default');

            return $cacheResult === 'test';
        } catch (Throwable $e) {
            return false;
        }
    }

    private function measureHealthCheckTime(): float
    {
        $startTime = microtime(true);
        $this->performComprehensiveHealthCheck();
        return (microtime(true) - $startTime) * 1000; // Convert to milliseconds
    }

    private function getApplicationUptime(): int
    {
        try {
            // Approximate uptime based on when metrics collection started
            $startFile = storage_path('app/metrics_start_time');

            if (!file_exists($startFile)) {
                file_put_contents($startFile, time());
                return 0;
            }

            $startTime = (int) file_get_contents($startFile);
            return time() - $startTime;
        } catch (Throwable $e) {
            return 0;
        }
    }
}
