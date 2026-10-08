<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PasskeyLoginController extends Controller
{
    private const SESSION_KEY = 'passkeys.login_ceremony';

    public function options(Request $request, PasskeyService $passkeys): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        $result = $passkeys->authenticationOptions();
        $request->session()->put(self::SESSION_KEY, $result['ceremony']);

        return $this->noStore(response()->json($result['options']));
    }

    public function store(Request $request, PasskeyService $passkeys): JsonResponse
    {
        $ceremony = $request->session()->pull(self::SESSION_KEY);

        $validated = $request->validate([
            'id' => ['required', 'string', 'max:4096'],
            'rawId' => ['required', 'string', 'max:4096'],
            'type' => ['required', 'string', 'in:public-key'],
            'response' => ['required', 'array'],
            'response.clientDataJSON' => ['required', 'string', 'max:16384'],
            'response.authenticatorData' => ['required', 'string', 'max:16384'],
            'response.signature' => ['required', 'string', 'max:16384'],
            'response.userHandle' => ['required', 'string', 'max:2048'],
            'clientExtensionResults' => ['sometimes', 'array'],
            'authenticatorAttachment' => ['sometimes', 'nullable', 'string', 'max:32'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        try {
            if (! is_array($ceremony)) {
                throw new \RuntimeException('Missing passkey ceremony.');
            }

            $user = $passkeys->authenticate($validated, $ceremony);
        } catch (Throwable) {
            return $this->verificationFailed();
        }

        Auth::guard('web')->login($user, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        // Pimpinan cannot access admin routes; discard a stale intended admin URL.
        $intendedPath = parse_url((string) $request->session()->get('url.intended', ''), PHP_URL_PATH);
        if ($user->role === 'pimpinan' && is_string($intendedPath)
            && ($intendedPath === '/admin' || str_starts_with($intendedPath, '/admin/'))) {
            $request->session()->forget('url.intended');
        }

        $defaultRedirect = match ($user->role) {
            'superadmin' => route('superadmin.dashboard'),
            'admin' => route('admin.dashboard'),
            'musyrif' => route('musyrif.dashboard'),
            'pimpinan' => route('pimpinan.dashboard'),
            default => url('/dashboard'),
        };
        $redirect = redirect()->intended($defaultRedirect)->getTargetUrl();

        return $this->noStore(response()->json([
            'message' => 'Login dengan passkey berhasil.',
            'redirect' => $redirect,
        ]));
    }

    private function verificationFailed(): JsonResponse
    {
        return $this->noStore(response()->json([
            'message' => 'Passkey tidak dapat diverifikasi. Silakan coba lagi atau gunakan password.',
            'errors' => [
                'passkey' => ['Passkey tidak dapat diverifikasi. Silakan coba lagi atau gunakan password.'],
            ],
        ], 422));
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
