<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Pulse\Facades\Pulse;
use Throwable;

class HealthController extends Controller
{
    protected array $checks = [];
    protected bool $allHealthy = true;
    protected int $httpStatus = 200;

    public function check(Request $request): \Illuminate\Http\JsonResponse
    {
        $startTime = microtime(true);

        // Perform all health checks
        $this->checkDatabase();
        $this->checkCache();
        $this->checkQueue();
        $this->checkHorizon();
        $this->checkQueueBacklogs();
        $this->checkRedis();

        // Calculate overall status
        $overallStatus = $this->allHealthy ? 'healthy' : 'unhealthy';
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        // Record health check metrics for Prometheus
        $this->recordHealthMetrics($overallStatus, $processingTime);

        $response = [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'checks' => $this->checks,
            'processing_time_ms' => $processingTime,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'uptime' => $this->getUptime(),
        ];

        return response()->json($response, $this->httpStatus);
    }

    protected function checkDatabase(): void
    {
        try {
            $startTime = microtime(true);

            // Test database connection and basic query
            DB::connection()->getPdo();
            $result = DB::selectOne('SELECT 1 as test');

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result && $result->test === 1) {
                $this->checks['database'] = [
                    'status' => 'healthy',
                    'message' => 'Database connection successful',
                    'response_time_ms' => $responseTime,
                    'connection' => config('database.default'),
                ];
            } else {
                throw new \Exception('Database query returned unexpected result');
            }

        } catch (Throwable $e) {
            $this->allHealthy = false;
            $this->httpStatus = 503;

            $this->checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkCache(): void
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_' . time();
            $testValue = 'test_' . time();

            // Test cache write and read
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($retrieved === $testValue) {
                $this->checks['cache'] = [
                    'status' => 'healthy',
                    'message' => 'Cache system functional',
                    'response_time_ms' => $responseTime,
                    'driver' => config('cache.default'),
                ];
            } else {
                throw new \Exception('Cache write/read test failed');
            }

        } catch (Throwable $e) {
            $this->allHealthy = false;
            $this->httpStatus = 503;

            $this->checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache system failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkQueue(): void
    {
        try {
            $startTime = microtime(true);

            // Check queue connection
            $size = Queue::size('default');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->checks['queue'] = [
                'status' => 'healthy',
                'message' => 'Queue system accessible',
                'response_time_ms' => $responseTime,
                'default_queue_size' => $size,
                'connection' => config('queue.default'),
            ];

        } catch (Throwable $e) {
            $this->allHealthy = false;
            $this->httpStatus = 503;

            $this->checks['queue'] = [
                'status' => 'unhealthy',
                'message' => 'Queue system failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkHorizon(): void
    {
        try {
            if (!class_exists(\Laravel\Horizon\Horizon::class)) {
                $this->checks['horizon'] = [
                    'status' => 'not_installed',
                    'message' => 'Horizon is not installed',
                ];
                return;
            }

            $startTime = microtime(true);

            // Check if Horizon supervisors are running
            if (app()->bound(MasterSupervisorRepository::class)) {
                $supervisors = app(MasterSupervisorRepository::class)->all();
                $activeSupervisors = count($supervisors);

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($activeSupervisors > 0) {
                    // Get supervisor details without using undefined method
                    $supervisorDetails = [];
                    foreach ($supervisors as $supervisor) {
                        $supervisorDetails[] = [
                            'name' => $supervisor->name ?? 'unknown',
                            'status' => $supervisor->status ?? 'unknown',
                            'processes' => $supervisor->processes->count() ?? 0,
                            'queues' => $supervisor->options['queue'] ?? [],
                        ];
                    }

                    $this->checks['horizon'] = [
                        'status' => 'healthy',
                        'message' => 'Horizon workers active',
                        'response_time_ms' => $responseTime,
                        'active_supervisors' => $activeSupervisors,
                        'supervisor_details' => $supervisorDetails,
                    ];
                } else {
                    $this->allHealthy = false;
                    $this->httpStatus = 503;

                    $this->checks['horizon'] = [
                        'status' => 'unhealthy',
                        'message' => 'No Horizon supervisors active',
                        'active_supervisors' => 0,
                    ];
                }
            } else {
                throw new \Exception('Horizon services not available');
            }

        } catch (Throwable $e) {
            $this->allHealthy = false;
            $this->httpStatus = 503;

            $this->checks['horizon'] = [
                'status' => 'unhealthy',
                'message' => 'Horizon check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkQueueBacklogs(): void
    {
        try {
            $queues = ['default', 'broadcasts'];
            $backlogInfo = [];
            $totalBacklog = 0;
            $maxBacklogThreshold = 100;

            foreach ($queues as $queueName) {
                $size = Queue::size($queueName);
                $backlogInfo[$queueName] = $size;
                $totalBacklog += $size;
            }

            $status = $totalBacklog > $maxBacklogThreshold ? 'warning' : 'healthy';

            if ($totalBacklog > $maxBacklogThreshold && $status === 'warning') {
                // Just warn
                $this->checks['queue_backlogs'] = [
                    'status' => 'warning',
                    'message' => "High queue backlog detected: {$totalBacklog} jobs",
                    'total_backlog' => $totalBacklog,
                    'queue_sizes' => $backlogInfo,
                    'threshold' => $maxBacklogThreshold,
                ];
            } else {
                $this->checks['queue_backlogs'] = [
                    'status' => 'healthy',
                    'message' => 'Queue backlogs within normal range',
                    'total_backlog' => $totalBacklog,
                    'queue_sizes' => $backlogInfo,
                    'threshold' => $maxBacklogThreshold,
                ];
            }

        } catch (Throwable $e) {
            $this->allHealthy = false;
            $this->httpStatus = 503;

            $this->checks['queue_backlogs'] = [
                'status' => 'unhealthy',
                'message' => 'Queue backlog check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkRedis(): void
    {
        try {
            if (config('database.redis.default') === null) {
                $this->checks['redis'] = [
                    'status' => 'not_configured',
                    'message' => 'Redis is not configured',
                ];
                return;
            }

            $startTime = microtime(true);

            // Testing Redis connection
            $redis = Redis::connection();
            $redis->ping();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->checks['redis'] = [
                'status' => 'healthy',
                'message' => 'Redis connection successful',
                'response_time_ms' => $responseTime,
            ];

        } catch (Throwable $e) {
            $this->allHealthy = false;
            $this->httpStatus = 503;

            $this->checks['redis'] = [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function recordHealthMetrics(string $status, float $processingTime): void
    {
        try {
            // Recordi ng overall health status for Prometheus
            Pulse::record(
                type: 'health_check',
                key: 'overall_status',
                value: $status === 'healthy' ? 1 : 0
            )->count();

            // Recording health check processing time
            Pulse::record(
                type: 'health_performance',
                key: 'check_duration_ms',
                value: $processingTime
            )->avg();

            // Recording individual service statuses
            foreach ($this->checks as $service => $check) {
                Pulse::record(
                    type: 'service_health',
                    key: $service . '_status',
                    value: $check['status'] === 'healthy' ? 1 : 0
                )->count();

                // Record response times if available
                if (isset($check['response_time_ms'])) {
                    Pulse::record(
                        type: 'service_performance',
                        key: $service . '_response_time',
                        value: $check['response_time_ms']
                    )->avg();
                }
            }

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to record health check metrics: ' . $e->getMessage());
        }
    }

    protected function getUptime(): string
    {
        try {
            $uptime = file_get_contents('/proc/uptime');
            $seconds = (int) explode(' ', $uptime)[0];
            return gmdate('H:i:s', $seconds);
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    protected function determineHttpStatus(): int
    {
        $criticalServices = ['database', 'queue', 'cache'];
        $unhealthyCount = 0;
        $warningCount = 0;

        foreach ($this->checks as $service => $check) {
            if ($check['status'] === 'unhealthy') {
                if (in_array($service, $criticalServices)) {
                    // Service Unavailable for critical services
                    return 503;
                }
                $unhealthyCount++;
            } elseif ($check['status'] === 'warning') {
                $warningCount++;
            }
        }

        if ($unhealthyCount > 0) {
            return 503; // Service Unavailable
        } elseif ($warningCount > 0) {
            return 200; // OK but with warnings
        }

        return 200; // All Good
    }
}
