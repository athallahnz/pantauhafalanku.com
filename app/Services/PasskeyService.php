<?php

namespace App\Services;

use App\Exceptions\PasskeyException;
use App\Models\PasskeyCredential;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;
use JsonException;
use lbuchs\WebAuthn\WebAuthn;
use LogicException;
use Throwable;

class PasskeyService
{
    private const TRANSPORTS = ['usb', 'nfc', 'ble', 'hybrid', 'internal'];

    public function userMayUsePasskeys(User $user): bool
    {
        return in_array($user->role, (array) config('passkeys.allowed_roles', []), true);
    }

    /**
     * @return array{options: array<string, mixed>, ceremony: array<string, mixed>}
     */
    public function registrationOptions(User $user): array
    {
        $this->assertEligibleUser($user);

        [$webAuthn, $rpId] = $this->webAuthn();
        $userHandle = $this->userHandleFor($user);
        $excludedCredentialIds = $user->passkeyCredentials()
            ->orderBy('id')
            ->pluck('credential_id')
            ->map(fn (string $id): string => self::base64urlDecode($id))
            ->all();

        $arguments = $webAuthn->getCreateArgs(
            $userHandle,
            $user->email ?: 'user-'.$user->getKey(),
            $user->name,
            $this->timeoutSeconds(),
            'required',
            'required',
            null,
            $excludedCredentialIds
        );

        $options = $this->serializeOptions($arguments);
        $options['publicKey']['pubKeyCredParams'] = array_values(array_filter(
            $options['publicKey']['pubKeyCredParams'] ?? [],
            static fn (mixed $parameter): bool => is_array($parameter)
                && in_array($parameter['alg'] ?? null, [-7, -257], true)
        ));
        unset($options['publicKey']['extensions']);

        return [
            'options' => $options,
            'ceremony' => [
                'challenge' => self::base64urlEncode($webAuthn->getChallenge()->getBinaryString()),
                'rp_id' => $rpId,
                'user_id' => (int) $user->getKey(),
                'user_handle' => self::base64urlEncode($userHandle),
                'expires_at' => now()->addSeconds($this->ceremonyTtlSeconds())->timestamp,
            ],
        ];
    }

    /**
     * @return array{options: array<string, mixed>, ceremony: array<string, mixed>}
     */
    public function authenticationOptions(): array
    {
        [$webAuthn, $rpId] = $this->webAuthn();
        $arguments = $webAuthn->getGetArgs(
            [],
            $this->timeoutSeconds(),
            true,
            true,
            true,
            true,
            true,
            'required'
        );

        return [
            'options' => $this->serializeOptions($arguments),
            'ceremony' => [
                'challenge' => self::base64urlEncode($webAuthn->getChallenge()->getBinaryString()),
                'rp_id' => $rpId,
                'expires_at' => now()->addSeconds($this->ceremonyTtlSeconds())->timestamp,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $credential
     * @param  array<string, mixed>  $ceremony
     */
    public function register(User $user, string $name, array $credential, array $ceremony): PasskeyCredential
    {
        try {
            $this->assertEligibleUser($user);
            $challenge = $this->challengeFromCeremony($ceremony);

            if ((int) ($ceremony['user_id'] ?? 0) !== (int) $user->getKey()) {
                throw PasskeyException::verificationFailed();
            }

            $userHandle = self::base64urlDecode((string) ($ceremony['user_handle'] ?? ''));
            if (strlen($userHandle) !== 32 || ! hash_equals($this->userHandleFor($user), $userHandle)) {
                throw PasskeyException::verificationFailed();
            }

            $credentialId = $this->credentialIdFromPayload($credential);
            $response = $this->response($credential);
            $clientDataJson = self::base64urlDecode((string) ($response['clientDataJSON'] ?? ''));
            $attestationObject = self::base64urlDecode((string) ($response['attestationObject'] ?? ''));
            $this->assertClientData($clientDataJson, 'webauthn.create', $challenge);

            [$webAuthn] = $this->webAuthn();
            $attestation = $webAuthn->processCreate(
                $clientDataJson,
                $attestationObject,
                $challenge,
                true,
                true,
                false,
                false
            );

            if (! is_string($attestation->credentialId ?? null)
                || ! hash_equals($credentialId, $attestation->credentialId)) {
                throw PasskeyException::verificationFailed();
            }

            $publicKey = (string) ($attestation->credentialPublicKey ?? '');
            if ($publicKey === '' || ! str_contains($publicKey, 'BEGIN PUBLIC KEY')) {
                throw PasskeyException::verificationFailed();
            }

            $encodedCredentialId = self::base64urlEncode($credentialId);

            return DB::transaction(function () use (
                $user,
                $name,
                $encodedCredentialId,
                $credentialId,
                $userHandle,
                $publicKey,
                $attestation,
                $response
            ): PasskeyCredential {
                return $user->passkeyCredentials()->create([
                    'name' => trim($name),
                    'credential_id' => $encodedCredentialId,
                    'credential_id_hash' => hash('sha256', $credentialId),
                    'user_handle' => self::base64urlEncode($userHandle),
                    'public_key' => $publicKey,
                    'signature_count' => max(0, (int) ($attestation->signatureCounter ?? 0)),
                    'transports' => $this->sanitizeTransports($response['transports'] ?? []),
                ]);
            });
        } catch (PasskeyException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw PasskeyException::verificationFailed($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $credential
     * @param  array<string, mixed>  $ceremony
     */
    public function authenticate(array $credential, array $ceremony): User
    {
        try {
            $challenge = $this->challengeFromCeremony($ceremony);
            $credentialId = $this->credentialIdFromPayload($credential);
            $response = $this->response($credential);
            $userHandle = self::base64urlDecode((string) ($response['userHandle'] ?? ''));

            if (strlen($userHandle) !== 32) {
                throw PasskeyException::verificationFailed();
            }

            $clientDataJson = self::base64urlDecode((string) ($response['clientDataJSON'] ?? ''));
            $authenticatorData = self::base64urlDecode((string) ($response['authenticatorData'] ?? ''));
            $signature = self::base64urlDecode((string) ($response['signature'] ?? ''));
            $this->assertClientData($clientDataJson, 'webauthn.get', $challenge);

            [$webAuthn] = $this->webAuthn();

            return DB::transaction(function () use (
                $webAuthn,
                $credentialId,
                $userHandle,
                $clientDataJson,
                $authenticatorData,
                $signature,
                $challenge
            ): User {
                $stored = PasskeyCredential::query()
                    ->where('credential_id_hash', hash('sha256', $credentialId))
                    ->lockForUpdate()
                    ->first();

                if (! $stored
                    || ! hash_equals(self::base64urlDecode($stored->credential_id), $credentialId)
                    || ! hash_equals(self::base64urlDecode($stored->user_handle), $userHandle)) {
                    throw PasskeyException::verificationFailed();
                }

                $user = $stored->user;
                if (! $user) {
                    throw PasskeyException::verificationFailed();
                }

                $this->assertEligibleUser($user);

                if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                    throw PasskeyException::verificationFailed();
                }

                $webAuthn->processGet(
                    $clientDataJson,
                    $authenticatorData,
                    $signature,
                    $stored->public_key,
                    $challenge,
                    (int) $stored->signature_count,
                    true,
                    true
                );

                $newCounter = $webAuthn->getSignatureCounter();
                if ($newCounter !== null) {
                    $stored->signature_count = $newCounter;
                }
                $stored->last_used_at = now();
                $stored->save();

                return $user;
            });
        } catch (PasskeyException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw PasskeyException::verificationFailed($exception);
        }
    }

    public static function base64urlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public static function base64urlDecode(string $value): string
    {
        if ($value === '' || str_contains($value, '=') || preg_match('/[^A-Za-z0-9_-]/', $value)) {
            throw PasskeyException::verificationFailed();
        }

        $remainder = strlen($value) % 4;
        if ($remainder === 1) {
            throw PasskeyException::verificationFailed();
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/').str_repeat('=', (4 - $remainder) % 4),
            true
        );

        if ($decoded === false || ! hash_equals(self::base64urlEncode($decoded), $value)) {
            throw PasskeyException::verificationFailed();
        }

        return $decoded;
    }

    private function assertEligibleUser(User $user): void
    {
        if (! $this->userMayUsePasskeys($user) || ! $user->isAccountActive()) {
            throw PasskeyException::verificationFailed();
        }
    }

    /**
     * @return array{0: WebAuthn, 1: string}
     */
    private function webAuthn(): array
    {
        [$rpName, $rpId] = $this->relyingPartyConfiguration();

        return [new WebAuthn($rpName, $rpId, ['none'], true), $rpId];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function relyingPartyConfiguration(): array
    {
        $rpName = trim((string) config('passkeys.rp_name'));
        $rpId = strtolower(rtrim(trim((string) config('passkeys.rp_id')), '.'));

        if ($rpName === '' || ! $this->validRpId($rpId)) {
            throw new LogicException('Konfigurasi relying party passkey tidak valid.');
        }

        $allowedOrigins = array_values(array_unique(array_map(
            fn (mixed $origin): string => $this->normalizeOrigin((string) $origin),
            (array) config('passkeys.allowed_origins', [])
        )));

        if ($allowedOrigins === []) {
            throw new LogicException('PASSKEY_ALLOWED_ORIGINS wajib diisi.');
        }

        foreach ($allowedOrigins as $origin) {
            $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
            $hostMatches = filter_var($rpId, FILTER_VALIDATE_IP)
                ? $host === $rpId
                : ($host === $rpId || str_ends_with($host, '.'.$rpId));

            if (! $hostMatches) {
                throw new LogicException('Origin passkey tidak berada dalam cakupan RP ID.');
            }
        }

        return [$rpName, $rpId];
    }

    private function validRpId(string $rpId): bool
    {
        if ($rpId === 'localhost' || filter_var($rpId, FILTER_VALIDATE_IP)) {
            return true;
        }

        return strlen($rpId) <= 253
            && preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $rpId) === 1;
    }

    private function normalizeOrigin(string $origin): string
    {
        $parts = parse_url(trim($origin));
        if (! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || (isset($parts['path']) && ! in_array($parts['path'], ['', '/'], true))) {
            throw new LogicException('Origin passkey tidak valid.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $isLoopback = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if ($scheme !== 'https' && ! ($scheme === 'http' && $isLoopback)) {
            throw new LogicException('Origin passkey wajib menggunakan HTTPS.');
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $isDefaultPort = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        $formattedHost = str_contains($host, ':') ? '['.$host.']' : $host;

        return $scheme.'://'.$formattedHost.($port && ! $isDefaultPort ? ':'.$port : '');
    }

    private function userHandleFor(User $user): string
    {
        $stored = $user->passkeyCredentials()->oldest('id')->value('user_handle');
        if (is_string($stored) && $stored !== '') {
            $decoded = self::base64urlDecode($stored);
            if (strlen($decoded) !== 32) {
                throw PasskeyException::verificationFailed();
            }

            return $decoded;
        }

        $applicationKey = (string) config('app.key');
        if ($applicationKey === '') {
            throw new LogicException('APP_KEY wajib tersedia untuk membuat user handle passkey.');
        }

        return hash_hmac('sha256', "simtaqu-passkey-user\0".$user->getKey(), $applicationKey, true);
    }

    /**
     * @param  array<string, mixed>  $ceremony
     */
    private function challengeFromCeremony(array $ceremony): string
    {
        if (! isset($ceremony['expires_at'])
            || ! is_numeric($ceremony['expires_at'])
            || (int) $ceremony['expires_at'] <= now()->timestamp) {
            throw PasskeyException::verificationFailed();
        }

        [, $rpId] = $this->relyingPartyConfiguration();
        $ceremonyRpId = (string) ($ceremony['rp_id'] ?? '');
        if ($ceremonyRpId === '' || ! hash_equals($rpId, $ceremonyRpId)) {
            throw PasskeyException::verificationFailed();
        }

        $challenge = self::base64urlDecode((string) ($ceremony['challenge'] ?? ''));
        if (strlen($challenge) < 16) {
            throw PasskeyException::verificationFailed();
        }

        return $challenge;
    }

    private function assertClientData(string $clientDataJson, string $expectedType, string $challenge): void
    {
        try {
            $clientData = json_decode($clientDataJson, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw PasskeyException::verificationFailed($exception);
        }

        if (! is_array($clientData)
            || ($clientData['type'] ?? null) !== $expectedType
            || ! is_string($clientData['challenge'] ?? null)
            || ! is_string($clientData['origin'] ?? null)
            || (($clientData['crossOrigin'] ?? false) !== false)
            || array_key_exists('topOrigin', $clientData)) {
            throw PasskeyException::verificationFailed();
        }

        if (! hash_equals($challenge, self::base64urlDecode($clientData['challenge']))) {
            throw PasskeyException::verificationFailed();
        }

        try {
            $origin = $this->normalizeOrigin($clientData['origin']);
            $allowedOrigins = array_map(
                fn (mixed $allowed): string => $this->normalizeOrigin((string) $allowed),
                (array) config('passkeys.allowed_origins', [])
            );
        } catch (LogicException $exception) {
            throw PasskeyException::verificationFailed($exception);
        }

        if (! in_array($origin, $allowedOrigins, true)) {
            throw PasskeyException::verificationFailed();
        }
    }

    /**
     * @param  array<string, mixed>  $credential
     */
    private function credentialIdFromPayload(array $credential): string
    {
        if (($credential['type'] ?? null) !== 'public-key'
            || ! is_string($credential['id'] ?? null)
            || ! is_string($credential['rawId'] ?? null)) {
            throw PasskeyException::verificationFailed();
        }

        $id = self::base64urlDecode($credential['id']);
        $rawId = self::base64urlDecode($credential['rawId']);
        if (strlen($id) > 1024 || ! hash_equals($id, $rawId)) {
            throw PasskeyException::verificationFailed();
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $credential
     * @return array<string, mixed>
     */
    private function response(array $credential): array
    {
        $response = $credential['response'] ?? null;
        if (! is_array($response)) {
            throw PasskeyException::verificationFailed();
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOptions(object $arguments): array
    {
        try {
            $json = json_encode($arguments, JSON_THROW_ON_ERROR);

            /** @var array<string, mixed> $options */
            $options = json_decode($json, true, 32, JSON_THROW_ON_ERROR);

            return $options;
        } catch (JsonException $exception) {
            throw new LogicException('Opsi passkey tidak dapat diserialisasi.', 0, $exception);
        }
    }

    /**
     * @return array<int, string>|null
     */
    private function sanitizeTransports(mixed $transports): ?array
    {
        if (! is_array($transports)) {
            return null;
        }

        $sanitized = array_values(array_unique(array_filter(
            $transports,
            static fn (mixed $transport): bool => is_string($transport)
                && in_array($transport, self::TRANSPORTS, true)
        )));

        return $sanitized === [] ? null : $sanitized;
    }

    private function timeoutSeconds(): int
    {
        return max(15, min(120, (int) config('passkeys.timeout_seconds', 60)));
    }

    private function ceremonyTtlSeconds(): int
    {
        return max(60, min(600, (int) config('passkeys.ceremony_ttl_seconds', 300)));
    }
}
