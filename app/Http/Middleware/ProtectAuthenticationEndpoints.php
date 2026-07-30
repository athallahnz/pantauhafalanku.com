<?php

namespace App\Http\Middleware;

use App\Services\Security\TurnstileVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ProtectAuthenticationEndpoints
{
    public function __construct(
        private readonly TurnstileVerifier $turnstile
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        if ($request->isMethod('GET') && $routeName === 'register') {
            $request->session()->put(
                'auth_security.registration_started_at',
                now()->getTimestamp()
            );

            return $next($request);
        }

        if (!$request->isMethod('POST')) {
            return $next($request);
        }

        if ($routeName === 'register') {
            $this->protectRegistration($request);
        }

        if (
            $routeName === 'login'
            && (bool) config('auth_security.turnstile.protect_login', true)
        ) {
            $this->turnstile->verify($request, 'login');
        }

        return $next($request);
    }

    private function protectRegistration(Request $request): void
    {
        $honeypot = trim((string) $request->input('company_website', ''));

        if ($honeypot !== '') {
            $this->rejectRegistration();
        }

        $startedAt = (int) $request->session()->get(
            'auth_security.registration_started_at',
            0
        );

        $elapsedSeconds = now()->getTimestamp() - $startedAt;
        $minimumSeconds = max(
            0,
            (int) config('auth_security.registration.minimum_form_seconds', 3)
        );
        $maximumSeconds = max(
            $minimumSeconds + 1,
            (int) config('auth_security.registration.maximum_form_seconds', 7200)
        );

        if (
            $startedAt <= 0
            || $elapsedSeconds < $minimumSeconds
            || $elapsedSeconds > $maximumSeconds
        ) {
            $this->rejectRegistration(
                'Formulir registrasi telah kedaluwarsa atau dikirim terlalu cepat. Muat ulang halaman dan coba kembali.'
            );
        }

        $ipKey = 'auth-register-ip:' . hash('sha256', (string) $request->ip());
        $ipMaxAttempts = max(
            1,
            (int) config('auth_security.registration.ip_max_attempts', 5)
        );
        $ipDecaySeconds = max(
            60,
            (int) config('auth_security.registration.ip_decay_seconds', 600)
        );

        if (RateLimiter::tooManyAttempts($ipKey, $ipMaxAttempts)) {
            $seconds = RateLimiter::availableIn($ipKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan registrasi. Coba kembali dalam {$seconds} detik.",
            ]);
        }

        RateLimiter::hit($ipKey, $ipDecaySeconds);

        if ((bool) config('auth_security.turnstile.protect_register', true)) {
            $this->turnstile->verify($request, 'register');
        }

        $email = mb_strtolower(trim((string) $request->input('email', '')));

        if ($email !== '') {
            $emailKey = 'auth-register-email:' . hash('sha256', $email);
            $emailMaxAttempts = max(
                1,
                (int) config('auth_security.registration.email_max_attempts', 5)
            );
            $emailDecaySeconds = max(
                60,
                (int) config('auth_security.registration.email_decay_seconds', 3600)
            );

            if (RateLimiter::tooManyAttempts($emailKey, $emailMaxAttempts)) {
                $seconds = RateLimiter::availableIn($emailKey);

                throw ValidationException::withMessages([
                    'email' => "Alamat email ini terlalu sering digunakan untuk registrasi. Coba kembali dalam {$seconds} detik.",
                ]);
            }

            RateLimiter::hit($emailKey, $emailDecaySeconds);
        }
    }

    private function rejectRegistration(
        string $message = 'Permintaan registrasi tidak dapat diproses.'
    ): never {
        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }
}
