<?php

namespace App\Providers;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // 1. DETEKSI LOGIN BERHASIL
        Event::listen(function (Login $event) {
            ActivityLog::create([
                'log_name'     => 'auth_login',
                'description'  => 'User berhasil login ke sistem',
                'subject_type' => get_class($event->user),
                'subject_id'   => $event->user->id,
                'causer_type'  => get_class($event->user),
                'causer_id'    => $event->user->id,
                'properties'   => ['email' => $event->user->email],
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);
        });

        // 2. DETEKSI LOGOUT
        Event::listen(function (Logout $event) {
            if ($event->user) {
                ActivityLog::create([
                    'log_name'     => 'auth_logout',
                    'description'  => 'User berhasil logout dari sistem',
                    'subject_type' => get_class($event->user),
                    'subject_id'   => $event->user->id,
                    'causer_type'  => get_class($event->user),
                    'causer_id'    => $event->user->id,
                    'properties'   => ['email' => $event->user->email],
                    'ip_address'   => request()->ip(),
                    'user_agent'   => request()->userAgent(),
                ]);
            }
        });

        // 3. DETEKSI LOGIN GAGAL (Password Salah / Email Tidak Terdaftar)
        // Ini fitur bonus keamanan yang sangat penting!
        Event::listen(function (Failed $event) {
            ActivityLog::create([
                'log_name'     => 'auth_failed',
                'description'  => 'Percobaan login gagal',
                'subject_type' => $event->user ? get_class($event->user) : null,
                'subject_id'   => $event->user ? $event->user->id : null,
                'causer_type'  => null, // Causer null karena belum berhasil login
                'causer_id'    => null,
                'properties'   => [
                    'attempted_email' => $event->credentials['email'] ?? 'unknown',
                    'reason'          => $event->user ? 'Password salah' : 'Email tidak ditemukan'
                ],
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
