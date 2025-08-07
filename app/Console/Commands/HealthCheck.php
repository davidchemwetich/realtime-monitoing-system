<?php

namespace App\Console\Commands;

use App\Http\Controllers\HealthController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class HealthCheck extends Command
{
    protected $signature = 'health:check {--json : Output as JSON} {--exit-code : Exit with non-zero code if unhealthy}';
    protected $description = 'Perform comprehensive application health check';

    public function handle()
    {
        $healthController = new HealthController();
        $request = new Request();

        $response = $healthController->check($request);
        $data = json_decode($response->getContent(), true);
        $httpStatus = $response->getStatusCode();

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
        } else {
            // Console-friendly output
            $this->info('=== Application Health Check ===');
            $this->line('Status: ' . $data['status']);
            $this->line('HTTP Status Code: ' . $httpStatus);
            $this->line('Timestamp: ' . $data['timestamp']);
            $this->line('Processing Time: ' . $data['processing_time_ms'] . 'ms');
            $this->line('Environment: ' . $data['environment']);
            $this->newLine();

            foreach ($data['checks'] as $service => $check) {
                $status = $check['status'];
                $icon = match ($status) {
                    'healthy' => '✅',
                    'warning' => '⚠️',
                    'unhealthy' => '❌',
                    'not_configured' => 'ℹ️',
                    'not_installed' => 'ℹ️',
                    default => '🔍'
                };

                $this->line("{$icon} {$service}: {$status} - {$check['message']}");

                if (isset($check['response_time_ms'])) {
                    $this->line("   Response time: {$check['response_time_ms']}ms");
                }

                if (isset($check['error'])) {
                    $this->line("   Error: {$check['error']}");
                }
            }
            $this->newLine();
        }

        if ($this->option('exit-code') && $httpStatus !== 200) {
            $this->error('❌ Application is not fully healthy!');
            return Command::FAILURE;
        }

        if ($data['status'] === 'healthy') {
            $this->info('✅ Application is healthy!');
            return Command::SUCCESS;
        } else {
            $this->warn('⚠️ Application has some issues but is functional');
            return Command::SUCCESS;
        }
    }
}
