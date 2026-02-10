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

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
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
        Fortify::resetPasswordView(function (Request $request) {
            return view('auth.reset-password', ['request' => $request]);
        });

        // EMAIL VERIFICATION VIEW
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // CUSTOM AUTH
        Fortify::authenticateUsing(function (Request $request) {

            $request->validate([
                'login' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);

            $login = trim($request->input('login'));

            $user = User::query()
                ->when(
                    filter_var($login, FILTER_VALIDATE_EMAIL),
                    fn($q) => $q->where('email', $login),
                    fn($q) => $q->where('nomor', $login)
                )
                ->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'login' => 'Email atau nomor tidak terdaftar.'
                ]);
            }

            if (!Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Password yang Anda masukkan salah.'
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
