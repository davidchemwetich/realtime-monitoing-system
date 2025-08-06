<?php

namespace App\Livewire;

use Livewire\Component;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class Chat extends Component
{
    public string $message = '';

    /**
     * Validation rules.
     */
    protected array $rules = [
        'message' => 'required|string|max:500',
    ];

    /**
     * Called directly from the frontend via `wire:submit.prevent`.
     */
    public function sendMessage()
    {
        // 1. Validate the message property
        $this->validate();

        try {
            $user = Auth::user();
            $messageContent = $this->message;

            // 2. Broadcast the event immediately.
            broadcast(new MessageSent($messageContent, $user))->toOthers();

            defer(function () use ($user, $messageContent) {
                // Safely log the message analytics
                Log::info('Deferred task: Logging message from Livewire component.', [
                    'user_id' => $user->id,
                    'message_length' => strlen($messageContent),
                ]);
                // The use of  Cache::increment to safely increase the count.
                Cache::increment('user_message_count_' . $user->id);
            });

            $this->reset('message');

        } catch (Throwable $e) {
            // If anything fails, we dispatch an event to the browser.
            Log::error('Error sending message from Livewire Chat component: ' . $e->getMessage());
            $this->dispatch('message-send-failed', error: 'Could not send message. Please check server logs.');
        }
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
