<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Facades\Pulse;
use Throwable;

class ChatController extends Controller
{
    public function notify(Request $request)
    {
        $startTime = microtime(true);

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
                    value: (microtime(true) - $startTime) * 1000 // Convert to milliseconds
                )->avg();
            });

            broadcast(new MessageSent($message, $user))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Message broadcast successfully.'
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