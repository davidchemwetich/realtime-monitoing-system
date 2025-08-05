<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Chat extends Component
{
    public $message = '';
    public $messages = [];
    public $activeUsers = [];

    protected $rules = [
        'message' => 'required|string|max:500',
    ];

    public function sendMessage()
    {
        $this->validate();

        // Sends via AJAX to our notify endpoint
        $this->dispatch('send-message', message: $this->message);
        $this->message = '';
    }

    public function render()
    {
        return view('livewire.chat');
    }
}