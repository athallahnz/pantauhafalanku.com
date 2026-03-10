<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserRegistered implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $totalPending;
    public $totalUser;

    public function __construct()
    {
        // Ambil data terbaru saat event dipicu
        $this->totalPending = User::where('is_approved', false)->count();
        $this->totalUser    = User::count();
    }

    public function broadcastOn()
    {
        // Harus sesuai dengan channel yang di-subscribe di JS
        return new Channel('admin-channel');
    }

    public function broadcastAs()
    {
        // Harus sesuai dengan bind di JS
        return 'user-registered';
    }
}
