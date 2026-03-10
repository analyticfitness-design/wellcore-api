<?php

namespace App\Events;

use App\Models\Checkin;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCheckinReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Checkin $checkin) {}

    public function broadcastOn(): array
    {
        $channels = [new Channel('activity-feed')];

        if ($this->checkin->user?->coach_id) {
            $channels[] = new PrivateChannel("coach.{$this->checkin->user->coach_id}");
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'type'         => 'checkin',
            'client_name'  => $this->checkin->user?->name ?? 'Cliente',
            'plan'         => $this->checkin->user?->plan ?? 'esencial',
            'bienestar'    => $this->checkin->bienestar,
            'timestamp'    => $this->checkin->created_at->toISOString(),
        ];
    }
}
