<?php

namespace App\Http\Controllers;

use App\Models\PasskeyCredential;
use App\Services\PasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AccountSecurityController extends Controller
{
    private const SESSION_KEY = 'passkeys.registration_ceremony';

    public function index(Request $request): View
    {
        $credentials = $request->user()
            ->passkeyCredentials()
            ->latest('created_at')
            ->get();

        return view('settings.security.index', compact('credentials'));
    }

    public function registrationOptions(Request $request, PasskeyService $passkeys): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'current_password' => ['required', 'string', 'max:4096'],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $result = $passkeys->registrationOptions($request->user());
        $result['ceremony']['name'] = trim($validated['name']);
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
            'response.attestationObject' => ['required', 'string', 'max:262144'],
            'response.transports' => ['sometimes', 'array', 'max:8'],
            'response.transports.*' => ['string', 'max:32'],
            'clientExtensionResults' => ['sometimes', 'array'],
            'authenticatorAttachment' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        try {
            if (! is_array($ceremony) || ! is_string($ceremony['name'] ?? null)) {
                throw new \RuntimeException('Missing passkey ceremony.');
            }

            $credential = $passkeys->register(
                $request->user(),
                $ceremony['name'],
                $validated,
                $ceremony
            );
        } catch (Throwable) {
            return $this->verificationFailed();
        }

        return $this->noStore(response()->json([
            'message' => 'Passkey berhasil ditambahkan.',
            'credential' => [
                'id' => $credential->getKey(),
                'name' => $credential->name,
                'created_at' => $credential->created_at?->toIso8601String(),
            ],
        ], 201));
    }

    public function update(Request $request, int $credential): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $passkey = $this->ownedCredential($request, $credential);
        $passkey->update(['name' => trim($validated['name'])]);

        return back()->with('success', 'Nama passkey berhasil diperbarui.');
    }

    public function destroy(Request $request, int $credential): RedirectResponse
    {
        $passkey = $this->ownedCredential($request, $credential);
        $passkey->delete();

        return back()->with('success', 'Passkey berhasil dicabut. Perangkat tersebut tidak dapat dipakai lagi.');
    }

    private function ownedCredential(Request $request, int $credential): PasskeyCredential
    {
        return $request->user()
            ->passkeyCredentials()
            ->whereKey($credential)
            ->firstOrFail();
    }

    private function verificationFailed(): JsonResponse
    {
        return $this->noStore(response()->json([
            'message' => 'Passkey tidak dapat diverifikasi. Mulai ulang proses penambahan passkey.',
            'errors' => [
                'passkey' => ['Passkey tidak dapat diverifikasi. Mulai ulang proses penambahan passkey.'],
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
