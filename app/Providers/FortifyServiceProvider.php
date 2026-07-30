<?php

namespace App\Providers;

use App\Actions\Auth\LoginResponse as CustomLoginResponse;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
        $this->app->bind(CreatesNewUsers::class, CreateNewUser::class);

        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                /*
                 * Fortify otomatis mengautentikasi akun setelah registrasi.
                 * Karena akun harus melalui approval, sesi langsung ditutup dan
                 * dirotasi agar tidak ada sesi pending yang tertinggal.
                 */
                auth()->guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('waiting.approval')
                    ->with('success', 'Registrasi berhasil. Akun menunggu persetujuan administrator.');
            }
        });
    }

    public function boot(): void
    {
        Fortify::username('login');

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(
            fn (Request $request) => view('auth.reset-password', ['request' => $request])
        );
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $request->validate([
                'login' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'max:4096'],
            ]);

            $login = trim((string) $request->input('login'));
            $normalizedEmail = mb_strtolower($login);

            $user = User::query()
                ->where(function ($query) use ($login, $normalizedEmail): void {
                    $query->where('email', $normalizedEmail)
                        ->orWhere('nomor', $login)
                        ->orWhereHas('santri', function ($santriQuery) use ($login): void {
                            $santriQuery->where('nis', $login);
                        });
                })
                ->first();

            if (!$user || !Hash::check((string) $request->input('password'), $user->password)) {
                throw ValidationException::withMessages([
                    'login' => 'Kredensial yang Anda masukkan tidak valid.',
                ]);
            }

            if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
                throw ValidationException::withMessages([
                    'login' => 'Alamat email akun belum diverifikasi.',
                ]);
            }

            $status = (string) ($user->account_status ?? 'pending');
            $isActive = $status === 'active' && (bool) $user->is_approved;

            if (!$isActive) {
                $message = match ($status) {
                    'pending' => 'Akun masih menunggu persetujuan administrator.',
                    'suspended' => 'Akun sedang ditangguhkan. Hubungi Super Admin.',
                    'rejected' => 'Permohonan akun telah ditolak.',
                    'archived' => 'Akun telah diarsipkan dan tidak dapat digunakan.',
                    default => 'Akun belum dapat digunakan.',
                };

                throw ValidationException::withMessages([
                    'login' => $message,
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request): array {
            $login = Str::lower(Str::limit(trim((string) $request->input('login')), 255, ''));
            $ip = (string) $request->ip();

            $identityLimit = max(
                1,
                (int) config('auth_security.login.identity_max_attempts_per_minute', 5)
            );
            $ipLimit = max(
                $identityLimit,
                (int) config('auth_security.login.ip_max_attempts_per_hour', 60)
            );

            return [
                // Membatasi brute-force pada satu identitas dari satu alamat IP.
                Limit::perMinute($identityLimit)
                    ->by('login-identity:' . $login . '|' . $ip),

                // Membatasi credential stuffing dengan banyak identitas dari IP yang sama.
                Limit::perHour($ipLimit)
                    ->by('login-ip:' . $ip),
            ];
        });

        RateLimiter::for('two-factor', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                'two-factor:' . (string) $request->session()->get('login.id', 'guest')
            );
        });
    }
}
