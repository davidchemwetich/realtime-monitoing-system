<?php

namespace App\Pulse\Recorders;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Config\Repository;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Concerns\ConfiguresAfterResolving;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Ignores;
use Laravel\Pulse\Recorders\Concerns\Sampling;

class BroadcastEventRecorder
{
    use ConfiguresAfterResolving, Ignores, Sampling;

    /**
     * The events to listen for.
     */
    public array $listen = [
        BroadcastEvent::class,
    ];

    /**
     * new recorder instance.
     */
    public function __construct(
        protected Pulse $pulse,
        protected Repository $config
    ) {
        //
    }

    /**
     * Recording the broadcast event.
     */
    public function record(BroadcastEvent $event): void
    {
        if (!$this->shouldSample()) {
            return;
        }

        $eventName = get_class($event->event);

        if ($this->shouldIgnore($eventName)) {
            return;
        }

        $this->pulse->record(
            type: 'broadcast_event',
            key: $eventName,
            timestamp: Carbon::now()->getTimestamp(),
            value: json_encode([
                'event' => $eventName,
                'channels' => $event->channels ?? [],
                'timestamp' => now()->toISOString(),
            ])
        )->count();
    }
}