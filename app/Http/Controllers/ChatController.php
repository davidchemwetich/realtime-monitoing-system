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
    public function notify(Request $request)
    {
        $startTime = microtime(true);

        // Enhanced validation
        $request->validate([
            'message' => 'required|string|max:500|min:1',
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

            // Record rate limiting metrics for monitoring
            $key = $user->id;
            $remaining = RateLimiter::remaining('chat-messages:' . $key, 30);

            // Record custom metrics for Pulse/Prometheus
            Pulse::record(
                type: 'chat_message',
                key: 'messages_sent',
                value: 1
            )->count();

            // Record rate limiting status
            Pulse::record(
                type: 'rate_limiting',
                key: 'chat_requests_remaining',
                value: $remaining
            )->avg();

            defer(function () use ($message, $user, $startTime) {
                Log::info('Message analytics logged.', [
                    'user_id' => $user->id,
                    'message_length' => strlen($message),
                    'processing_time' => microtime(true) - $startTime,
                ]);

                // Record processing time for monitoring
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
                'rate_limit_remaining' => $remaining,
                'rate_limit_reset_in' => RateLimiter::availableIn('chat-messages:' . $key)
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
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
