<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    /**
     * This method accepts a message and broadcasts 
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


            defer(function () use ($message, $user) {
                Log::info('Logging message analytics.', [
                    'user_id' => $user->id,
                    'message_length' => strlen($message),
                ]);
            });


            broadcast(new MessageSent($message, $user))->toOthers();


            return response()->json([
                'success' => true,
                'message' => 'Message broadcast successfully.'
            ]);

        } catch (Throwable $e) {

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