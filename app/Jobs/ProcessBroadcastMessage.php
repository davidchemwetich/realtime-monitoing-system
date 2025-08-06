<?php

namespace App\Jobs;

use App\Events\MessageSent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessBroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;
    public $user;
    public $tries = 2;
    public $timeout = 30;
    public $backoff = [5, 10]; // Retry after 5 seconds, then 10 seconds

    /**
     * Create a new job instance.
     */
    public function __construct($message, $user)
    {
        $this->message = $message;
        $this->user = $user;

        // Queue this job on the broadcasts queue
        $this->onQueue('broadcasts');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing broadcast message', [
                'user_id' => $this->user->id,
                'message_length' => strlen($this->message),
                'queue' => 'broadcasts'
            ]);

            // Process message analytics
            $this->processMessageAnalytics();

            // Broadcast the event
            broadcast(new MessageSent($this->message, $this->user))->toOthers();

            Log::info('Broadcast message processed successfully', [
                'user_id' => $this->user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process broadcast message', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Process message analytics and store in cache
     */
    private function processMessageAnalytics(): void
    {
        $cacheKey = 'user_message_count_' . $this->user->id;
        $dailyKey = 'daily_messages_' . now()->format('Y-m-d');

        // Increment user message count
        Cache::increment($cacheKey);

        // Increment daily message count
        Cache::increment($dailyKey);

        // Store message metadata
        $messageData = [
            'user_id' => $this->user->id,
            'message_length' => strlen($this->message),
            'timestamp' => now()->toISOString(),
            'processed_at' => now()->toISOString()
        ];

        // Store in cache for recent messages (expires in 1 hour)
        $recentMessages = Cache::get('recent_messages', []);
        $recentMessages[] = $messageData;

        // Keep only last 100 messages
        if (count($recentMessages) > 100) {
            $recentMessages = array_slice($recentMessages, -100);
        }

        Cache::put('recent_messages', $recentMessages, 3600); // 1 hour
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessBroadcastMessage job failed permanently', [
            'user_id' => $this->user->id,
            'message_preview' => substr($this->message, 0, 50) . '...',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}