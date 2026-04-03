<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use App\Actions\Auth\LoginResponse as CustomLoginResponse;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\Contracts\RegisterResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Santri;
use App\Models\Musyrif;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ini buat Response Login Mas yang udah ada
        $this->app->singleton(\Laravel\Fortify\Contracts\LoginResponse::class, \App\Actions\Auth\LoginResponse::class);

        // PAKSA BINDING UNTUK REGISTER DISINI:
        $this->app->bind(
            \Laravel\Fortify\Contracts\CreatesNewUsers::class,
            \App\Actions\Fortify\CreateNewUser::class
        );

        // Di dalam register()
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                // Setelah daftar, paksa logout (karena Fortify otomatis login)
                // lalu lempar ke halaman waiting
                auth()->logout();
                return redirect()->route('waiting.approval');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // WAJIB: override username field
        Fortify::username('login');

        // LOGIN VIEW
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // REGISTER VIEW
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // FORGOT PASSWORD VIEW
        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        // RESET PASSWORD VIEW
        Fortify::resetPasswordView(function ($request) {
            return view('auth.reset-password', ['request' => $request]);
        });

        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // EMAIL VERIFICATION VIEW
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // CUSTOM AUTH
        Fortify::authenticateUsing(function (Request $request) {
            $request->validate([
                'login'    => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);

            $login = trim($request->input('login'));

            // UPDATE DI SINI: Query pencarian user diperluas
            $user = User::query()
                ->where('email', $login)
                ->orWhere('nomor', $login)
                ->orWhereHas('santri', function ($query) use ($login) {
                    $query->where('nis', $login);
                })
                ->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    // Update pesan error-nya
                    'login' => 'Email, Nomor, atau NIS/NIM tidak terdaftar.',
                ]);
            }

            if (!Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Password yang Anda masukkan salah.',
                ]);
            }

            // Pengecekan verifikasi email
            if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
                return null;
            }

            if (!$user->is_approved) {
                throw ValidationException::withMessages([
                    'login' => 'Akun Anda sudah diverifikasi, namun masih menunggu persetujuan Admin.',
                ]);
            }

            return $user;
        });

        // RATE LIMITER
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by(
                Str::lower($request->input('login')) . '|' . $request->ip()
            );
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
