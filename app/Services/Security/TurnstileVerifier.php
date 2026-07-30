<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class TurnstileVerifier
{
    public function verify(Request $request, string $expectedAction): void
    {
        if (!(bool) config('auth_security.turnstile.enabled', false)) {
            return;
        }

        $secretKey = trim((string) config('auth_security.turnstile.secret_key'));
        $token = trim((string) $request->input('cf-turnstile-response', ''));

        if ($secretKey === '') {
            Log::critical('Turnstile aktif tetapi TURNSTILE_SECRET_KEY belum dikonfigurasi.');

            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Verifikasi keamanan belum dikonfigurasi dengan benar. Hubungi administrator.',
            ]);
        }

        if ($token === '') {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Selesaikan verifikasi keamanan terlebih dahulu.',
            ]);
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('auth_security.turnstile.timeout_seconds', 5))
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Turnstile Siteverify returned HTTP ' . $response->status());
            }

            $payload = $response->json();

            if (!is_array($payload) || ($payload['success'] ?? false) !== true) {
                Log::warning('Turnstile verification rejected.', [
                    'route' => $request->route()?->getName(),
                    'ip' => $request->ip(),
                    'error_codes' => is_array($payload) ? ($payload['error-codes'] ?? []) : [],
                ]);

                throw ValidationException::withMessages([
                    'cf-turnstile-response' => 'Verifikasi keamanan gagal atau telah kedaluwarsa. Silakan coba lagi.',
                ]);
            }

            $actualAction = trim((string) ($payload['action'] ?? ''));

            $isLocalTestKey = app()->environment([
                'local',
                'testing',
            ]) && in_array($secretKey, [
                '1x0000000000000000000000000000000AA',
                '2x0000000000000000000000000000000AA',
                '3x0000000000000000000000000000000AA',
            ], true);

            $allowedActions = $isLocalTestKey
                ? [$expectedAction, 'test']
                : [$expectedAction];

            if (!in_array($actualAction, $allowedActions, true)) {
                Log::warning('Turnstile action mismatch.', [
                    'expected_action' => $expectedAction,
                    'actual_action' => $actualAction,
                    'ip' => $request->ip(),
                ]);

                throw ValidationException::withMessages([
                    'cf-turnstile-response' =>
                    'Verifikasi keamanan tidak sesuai dengan formulir yang dikirim.',
                ]);
            }

            $allowedHostnames = collect(config('auth_security.turnstile.allowed_hostnames', []))
                ->filter(fn($hostname): bool => is_string($hostname) && trim($hostname) !== '')
                ->map(fn(string $hostname): string => mb_strtolower(trim($hostname)))
                ->values();

            if ($allowedHostnames->isNotEmpty()) {
                $actualHostname = mb_strtolower(trim((string) ($payload['hostname'] ?? '')));

                if ($actualHostname === '' || !$allowedHostnames->contains($actualHostname)) {
                    Log::warning('Turnstile hostname mismatch.', [
                        'actual_hostname' => $actualHostname,
                        'allowed_hostnames' => $allowedHostnames->all(),
                        'ip' => $request->ip(),
                    ]);

                    throw ValidationException::withMessages([
                        'cf-turnstile-response' => 'Verifikasi keamanan berasal dari host yang tidak dikenali.',
                    ]);
                }
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Turnstile verification unavailable.', [
                'message' => $exception->getMessage(),
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            if ((bool) config('auth_security.turnstile.fail_open', false)) {
                return;
            }

            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Layanan verifikasi keamanan sedang tidak tersedia. Silakan coba beberapa saat lagi.',
            ]);
        }
    }
}
