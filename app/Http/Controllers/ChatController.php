<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBroadcastMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    /**
     * This method accepts a message and queues it for broadcast processing
     */
    public function notify(Request $request)
    {
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

            // Log request analytics using defer for performance
            defer(function () use ($message, $user) {
                Log::info('Message queued for processing', [
                    'user_id' => $user->id,
                    'message_length' => strlen($message),
                    'queued_at' => now()->toISOString()
                ]);
            });

            // Dispatch the job to the broadcasts queue
            ProcessBroadcastMessage::dispatch($message, $user);

            return response()->json([
                'success' => true,
                'message' => 'Message queued for broadcast successfully.'
            ]);

        } catch (Throwable $e) {
            Log::error('Error in ChatController@notify: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred while queuing the message.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}