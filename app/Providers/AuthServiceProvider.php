<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

// 1. Tambahkan 2 baris ini untuk memanggil fitur Notifikasi Email Laravel
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // 2. Masukkan custom logika email Lupa Password di sini
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            return (new MailMessage)
                ->subject('🔑 Notifikasi Reset Password - SIMTAQU')
                ->greeting('Assalamu\'alaikum, ' . $notifiable->name . '!')
                ->line('Anda menerima email ini karena kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda di Sistem Informasi Tahfidz Qur\'an (SIMTAQU).')
                ->action('Atur Ulang Kata Sandi', url(route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)))
                ->line('Tautan pengaturan ulang kata sandi ini akan kedaluwarsa dalam 60 menit.')
                ->line('Jika Anda tidak merasa meminta pengaturan ulang kata sandi, abaikan saja email ini. Akun Anda tetap aman.')
                ->salutation('Wassalamu\'alaikum, Tim SIMTAQU');
        });
    }
}
