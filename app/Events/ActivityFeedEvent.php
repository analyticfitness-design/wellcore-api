<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityFeedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,
        public array $payload
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('activity-feed');
    }

    public function broadcastWith(): array
    {
        return array_merge(['type' => $this->type], $this->payload);
    }
}
