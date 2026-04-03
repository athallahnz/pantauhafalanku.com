<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MusyrifAbsenEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $musyrifId;
    public string $nama;
    public string $status;
    public string $waktu;

    public function __construct(int $musyrifId, string $nama, string $status)
    {
        $this->musyrifId = $musyrifId;
        $this->nama = $nama;
        $this->status = $status;
        $this->waktu = now()->format('H:i');
    }

    public function broadcastOn(): array
    {
        // Samakan dengan subscriber di JS
        return [new Channel('dashboard-channel')];
    }

    public function broadcastAs(): string
    {
        // Samakan dengan .bind() di JS
        return 'musyrif-absen-event';
    }
}
