<?php
// app/View/Components/PulseChatMetrics.php

namespace App\View\Components;

use Illuminate\View\Component;
use Laravel\Pulse\Facades\Pulse;

class PulseChatMetrics extends Component
{
    public function render()
    {
        $metrics = [
            'total_messages' => Pulse::values('chat_message', 'messages_sent'),
            'avg_processing_time' => Pulse::values('chat_performance', 'message_processing_time'),
            'error_count' => Pulse::values('chat_errors', 'message_send_failures'),
        ];

        return view('components.pulse-chat-metrics', compact('metrics'));
    }
}