<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Pulse\Facades\Pulse;
use Throwable;

class ChatController extends Controller
{
    /**
     * This method accepts a message and broadcasts with rate limiting
     */
    public function notify(Request $request)
    {
        $startTime = microtime(true);

        // Check rate limiting first
        $key = $request->user()?->id ?: $request->ip();

        if (RateLimiter::tooManyAttempts('chat-messages:' . $key, 30)) {
            $retryAfter = RateLimiter::availableIn('chat-messages:' . $key);

            // Record rate limit hit for monitoring
            Pulse::record(
                type: 'chat_rate_limits',
                key: 'rate_limit_hits',
                value: 1
            )->count();

            return response()->json([
                'success' => false,
                'message' => 'Too many messages sent. Please slow down.',
                'retry_after' => $retryAfter
            ], 429);
        }

        // Increment rate limiter
        RateLimiter::hit('chat-messages:' . $key, 60); // 1 minute decay

        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication error. User not found.'
                ], 401);
            }

            $message = $request->input('message');

            // Record custom metric for message processing
            Pulse::record(
                type: 'chat_message',
                key: 'messages_sent',
                value: 1
            )->count();

            defer(function () use ($message, $user, $startTime) {
                Log::info('Logging message analytics.', [
                    'user_id' => $user->id,
                    'message_length' => strlen($message),
                    'processing_time' => microtime(true) - $startTime,
                ]);

                // Record processing time
                Pulse::record(
                    type: 'chat_performance',
                    key: 'message_processing_time',
                    value: (microtime(true) - $startTime) * 1000
                )->avg();
            });

            broadcast(new MessageSent($message, $user))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Message broadcast successfully.',
                'rate_limit_remaining' => RateLimiter::remaining('chat-messages:' . $key, 30)
            ]);

        } catch (Throwable $e) {
            // Record error metrics
            Pulse::record(
                type: 'chat_errors',
                key: 'message_send_failures',
                value: 1
            )->count();

            Log::error('Error in ChatController@notify: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred while sending the message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
