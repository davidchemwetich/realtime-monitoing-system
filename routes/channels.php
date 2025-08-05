<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat-room', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=0ea5e9&color=fff',
    ];
});