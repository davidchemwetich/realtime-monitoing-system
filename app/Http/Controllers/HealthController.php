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
        $this->checkBroadcasting();

        // Calculate overall status and appropriate HTTP status code
        $overallStatus = $this->allHealthy ? 'healthy' : 'unhealthy';
        $this->httpStatus = $this->determineHttpStatus();
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        // Record health check metrics for Prometheus monitoring
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
            $this->checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
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
            $this->checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache system failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkQueue(): void
    {
        try {
            $startTime = microtime(true);

            // Check queue connection and basic functionality
            $defaultSize = Queue::size('default');
            $broadcastsSize = Queue::size('broadcasts');

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->checks['queue'] = [
                'status' => 'healthy',
                'message' => 'Queue system accessible',
                'response_time_ms' => $responseTime,
                'queue_sizes' => [
                    'default' => $defaultSize,
                    'broadcasts' => $broadcastsSize
                ],
                'connection' => config('queue.default'),
            ];

        } catch (Throwable $e) {
            $this->allHealthy = false;
            $this->checks['queue'] = [
                'status' => 'unhealthy',
                'message' => 'Queue system failed',
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
                    // Get supervisor details
                    $supervisorDetails = [];
                    foreach ($supervisors as $supervisor) {
                        $supervisorDetails[] = [
                            'name' => $supervisor->name ?? 'unknown',
                            'status' => $supervisor->status ?? 'unknown',
                            'processes' => $supervisor->processes->count() ?? 0,
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
            $this->checks['horizon'] = [
                'status' => 'unhealthy',
                'message' => 'Horizon check failed',
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

            if ($totalBacklog > $maxBacklogThreshold) {
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
            $this->checks['queue_backlogs'] = [
                'status' => 'unhealthy',
                'message' => 'Queue backlog check failed',
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

            // Test Redis connection
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
            $this->checks['redis'] = [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkBroadcasting(): void
    {
        try {
            // Check if Reverb/broadcasting is configured
            $broadcastDriver = config('broadcasting.default');

            if ($broadcastDriver === 'null' || !$broadcastDriver) {
                $this->checks['broadcasting'] = [
                    'status' => 'not_configured',
                    'message' => 'Broadcasting is not configured',
                ];
                return;
            }

            $this->checks['broadcasting'] = [
                'status' => 'healthy',
                'message' => 'Broadcasting system configured',
                'driver' => $broadcastDriver,
                'reverb_host' => config('reverb.host'),
                'reverb_port' => config('reverb.port'),
            ];

        } catch (Throwable $e) {
            $this->checks['broadcasting'] = [
                'status' => 'warning',
                'message' => 'Broadcasting check inconclusive',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function determineHttpStatus(): int
    {
        $criticalServices = ['database', 'queue', 'cache'];
        $criticalFailures = 0;
        $warnings = 0;

        foreach ($this->checks as $service => $check) {
            if ($check['status'] === 'unhealthy') {
                if (in_array($service, $criticalServices)) {
                    return 503; // Service Unavailable for critical services
                }
                $criticalFailures++;
            } elseif ($check['status'] === 'warning') {
                $warnings++;
            }
        }

        if ($criticalFailures > 0) {
            return 503; // Service Unavailable
        } elseif ($warnings > 0) {
            return 200; // OK with warnings but working
        }

        return 200; // All Good
    }

    protected function recordHealthMetrics(string $status, float $processingTime): void
    {
        try {
            // Record overall health status for Prometheus
            Pulse::record(
                type: 'health_check',
                key: 'overall_status',
                value: $status === 'healthy' ? 1 : 0
            )->count();

            // Record health check processing time
            Pulse::record(
                type: 'health_performance',
                key: 'check_duration_ms',
                value: $processingTime
            )->avg();

            // Record individual service statuses
            foreach ($this->checks as $service => $check) {
                $healthValue = match ($check['status']) {
                    'healthy' => 1,
                    'warning' => 0.5,
                    'unhealthy' => 0,
                    default => 0
                };

                Pulse::record(
                    type: 'service_health',
                    key: $service . '_status',
                    value: $healthValue
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
            // Try to read system uptime (Linux/Unix)
            if (file_exists('/proc/uptime')) {
                $uptime = file_get_contents('/proc/uptime');
                $seconds = (int) explode(' ', $uptime)[0];
                return gmdate('H:i:s', $seconds);
            }

            // Fallback for Docker or any other system
            return 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
}
