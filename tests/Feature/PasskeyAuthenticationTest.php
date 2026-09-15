<?php

namespace Tests\Feature;

use App\Exceptions\PasskeyException;
use App\Models\PasskeyCredential;
use App\Models\User;
use App\Services\PasskeyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PasskeyAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'app.url' => 'https://simtaqu.test',
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'passkeys.rp_name' => 'SIMTAQU Test',
            'passkeys.rp_id' => 'simtaqu.test',
            'passkeys.allowed_origins' => ['https://simtaqu.test'],
            'passkeys.login_attempts_per_minute' => 1000,
            'passkeys.management_attempts_per_minute' => 1000,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::dropAllTables();
        $this->createTestSchema();
    }

    public function test_password_form_and_passkey_login_are_both_available(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('Masuk ke Akun')
            ->assertSee('Masuk dengan Passkey');
    }

    public function test_existing_password_login_still_works(): void
    {
        $user = $this->createUser('admin', 'secret-pass');

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'secret-pass',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_authentication_options_are_usernameless_and_require_verification(): void
    {
        $response = $this->withHeader('Host', 'attacker.example')
            ->postJson('/passkeys/login/options')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('publicKey.userVerification', 'required')
            ->assertJsonPath('publicKey.rpId', 'simtaqu.test');

        $this->assertArrayNotHasKey('allowCredentials', $response->json('publicKey'));
        $this->assertNotEmpty($response->json('publicKey.challenge'));
        $response->assertSessionHas('passkeys.login_ceremony');
    }

    public function test_registration_requires_current_password_and_discoverable_credential(): void
    {
        $user = $this->createUser('admin', 'secret-pass');

        $this->actingAs($user)
            ->postJson(route('account.security.passkeys.options'), [
                'name' => 'Laptop Kantor',
                'current_password' => 'wrong-pass',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $first = $this->actingAs($user)
            ->postJson(route('account.security.passkeys.options'), [
                'name' => 'Laptop Kantor',
                'current_password' => 'secret-pass',
            ])
            ->assertOk()
            ->assertJsonPath('publicKey.authenticatorSelection.userVerification', 'required')
            ->assertJsonPath('publicKey.authenticatorSelection.residentKey', 'required')
            ->assertJsonPath('publicKey.authenticatorSelection.requireResidentKey', true)
            ->assertJsonPath('publicKey.attestation', 'none')
            ->assertSessionHas('passkeys.registration_ceremony.name', 'Laptop Kantor');

        $this->assertSame(
            [-7, -257],
            array_column($first->json('publicKey.pubKeyCredParams'), 'alg')
        );

        $second = $this->actingAs($user)
            ->postJson(route('account.security.passkeys.options'), [
                'name' => 'Ponsel',
                'current_password' => 'secret-pass',
            ])
            ->assertOk();

        $this->assertSame(
            $first->json('publicKey.user.id'),
            $second->json('publicKey.user.id'),
            'User handle harus stabil agar satu akun dapat memakai banyak perangkat.'
        );

        $existingId = random_bytes(32);
        $user->passkeyCredentials()->create([
            'name' => 'Perangkat Pertama',
            'credential_id' => PasskeyService::base64urlEncode($existingId),
            'credential_id_hash' => hash('sha256', $existingId),
            'user_handle' => $first->json('publicKey.user.id'),
            'public_key' => "-----BEGIN PUBLIC KEY-----\ninvalid-test-key\n-----END PUBLIC KEY-----\n",
            'signature_count' => 0,
        ]);

        $third = $this->actingAs($user)
            ->postJson(route('account.security.passkeys.options'), [
                'name' => 'Tablet',
                'current_password' => 'secret-pass',
            ])
            ->assertOk();

        $this->assertSame($first->json('publicKey.user.id'), $third->json('publicKey.user.id'));
        $this->assertSame(
            PasskeyService::base64urlEncode($existingId),
            $third->json('publicKey.excludeCredentials.0.id')
        );
    }

    public function test_only_scoped_roles_can_open_account_security(): void
    {
        foreach (['superadmin', 'admin', 'pimpinan', 'musyrif'] as $role) {
            $this->actingAs($this->createUser($role))
                ->get(route('account.security.index'))
                ->assertOk()
                ->assertSee('Keamanan Akun');
        }

        $this->actingAs($this->createUser('santri'))
            ->get(route('account.security.index'))
            ->assertForbidden();
    }

    public function test_multiple_passkeys_can_be_listed_renamed_and_revoked_by_owner(): void
    {
        $owner = $this->createUser('admin');
        $other = $this->createUser('admin');
        $first = $this->createCredential($owner, 'Laptop');
        $second = $this->createCredential($owner, 'Ponsel');
        $foreign = $this->createCredential($other, 'Milik Orang Lain');

        $this->actingAs($owner)
            ->get(route('account.security.index'))
            ->assertOk()
            ->assertSee('Laptop')
            ->assertSee('Ponsel')
            ->assertDontSee('Milik Orang Lain');

        $this->actingAs($owner)
            ->patch(route('account.security.passkeys.update', $first), ['name' => 'MacBook Kantor'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('passkey_credentials', [
            'id' => $first->id,
            'user_id' => $owner->id,
            'name' => 'MacBook Kantor',
        ]);

        $this->actingAs($owner)
            ->patch(route('account.security.passkeys.update', $foreign), ['name' => 'Diambil Alih'])
            ->assertNotFound();

        $this->actingAs($owner)
            ->delete(route('account.security.passkeys.destroy', $second))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('passkey_credentials', ['id' => $second->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_registration_ceremony_is_single_use_and_name_is_bound_to_session(): void
    {
        $user = $this->createUser('admin');
        $created = new PasskeyCredential(['name' => 'Kunci Sesi']);
        $created->id = 123;
        $created->created_at = now();

        $mock = Mockery::mock(PasskeyService::class);
        $mock->shouldReceive('register')
            ->once()
            ->withArgs(fn (User $actualUser, string $name, array $payload, array $ceremony): bool => $actualUser->is($user)
                && $name === 'Kunci Sesi'
                && $ceremony['name'] === 'Kunci Sesi'
                && $payload['type'] === 'public-key')
            ->andReturn($created);
        $this->app->instance(PasskeyService::class, $mock);

        $payload = $this->dummyRegistrationPayload();
        $session = ['passkeys.registration_ceremony' => ['name' => 'Kunci Sesi']];

        $this->actingAs($user)
            ->withSession($session)
            ->postJson(route('account.security.passkeys.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('credential.name', 'Kunci Sesi');

        $this->actingAs($user)
            ->postJson(route('account.security.passkeys.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.passkey.0', 'Passkey tidak dapat diverifikasi. Mulai ulang proses penambahan passkey.');
    }

    public function test_passkey_login_creates_a_normal_authenticated_session(): void
    {
        $user = $this->createUser('musyrif');
        $mock = Mockery::mock(PasskeyService::class);
        $mock->shouldReceive('authenticate')
            ->once()
            ->withArgs(fn (array $payload, array $ceremony): bool => $payload['type'] === 'public-key' && $ceremony['challenge'] === 'challenge')
            ->andReturn($user);
        $this->app->instance(PasskeyService::class, $mock);

        $this->withSession(['passkeys.login_ceremony' => ['challenge' => 'challenge']])
            ->postJson(route('passkeys.login.store'), $this->dummyAssertionPayload())
            ->assertOk()
            ->assertJsonPath('redirect', route('musyrif.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_assertion_is_verified_and_signature_counter_is_updated(): void
    {
        $user = $this->createUser('admin');
        [$stored, $privateKey, $credentialId, $userHandle] = $this->cryptographicCredential($user);
        $challenge = random_bytes(32);
        $payload = $this->signedAssertion(
            $privateKey,
            $credentialId,
            $userHandle,
            $challenge,
            'https://simtaqu.test',
            0x05,
            1
        );

        $authenticated = app(PasskeyService::class)->authenticate(
            $payload,
            $this->ceremony($challenge)
        );

        $this->assertTrue($authenticated->is($user));
        $this->assertSame(1, $stored->fresh()->signature_count);
        $this->assertNotNull($stored->fresh()->last_used_at);
    }

    public function test_none_attestation_registration_creates_a_usable_passkey(): void
    {
        $user = $this->createUser('pimpinan');
        $service = app(PasskeyService::class);
        $registration = $service->registrationOptions($user);
        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $privateKey);
        $credentialId = random_bytes(32);

        $stored = $service->register(
            $user,
            'Ponsel Utama',
            $this->registrationPayload(
                $privateKey,
                $credentialId,
                $registration['ceremony']['challenge'],
                1
            ),
            $registration['ceremony']
        );

        $this->assertSame('Ponsel Utama', $stored->name);
        $this->assertSame(1, $stored->signature_count);
        $this->assertSame(['internal'], $stored->transports);
        $this->assertSame(
            $registration['ceremony']['user_handle'],
            $stored->user_handle
        );

        $loginChallenge = random_bytes(32);
        $authenticated = $service->authenticate(
            $this->signedAssertion(
                $privateKey,
                $credentialId,
                PasskeyService::base64urlDecode($stored->user_handle),
                $loginChallenge,
                'https://simtaqu.test',
                0x05,
                2
            ),
            $this->ceremony($loginChallenge)
        );

        $this->assertTrue($authenticated->is($user));
        $this->assertSame(2, $stored->fresh()->signature_count);
    }

    public function test_assertion_without_user_verification_is_rejected(): void
    {
        $user = $this->createUser('admin');
        [, $privateKey, $credentialId, $userHandle] = $this->cryptographicCredential($user);
        $challenge = random_bytes(32);

        $this->expectException(PasskeyException::class);

        app(PasskeyService::class)->authenticate(
            $this->signedAssertion(
                $privateKey,
                $credentialId,
                $userHandle,
                $challenge,
                'https://simtaqu.test',
                0x01,
                1
            ),
            $this->ceremony($challenge)
        );
    }

    public function test_assertion_from_origin_outside_exact_allowlist_is_rejected(): void
    {
        $user = $this->createUser('admin');
        [, $privateKey, $credentialId, $userHandle] = $this->cryptographicCredential($user);
        $challenge = random_bytes(32);

        $this->expectException(PasskeyException::class);

        app(PasskeyService::class)->authenticate(
            $this->signedAssertion(
                $privateKey,
                $credentialId,
                $userHandle,
                $challenge,
                'https://sub.simtaqu.test',
                0x05,
                1
            ),
            $this->ceremony($challenge)
        );
    }

    public function test_inactive_or_unscoped_user_cannot_authenticate_with_passkey(): void
    {
        foreach ([
            ['role' => 'santri', 'status' => 'active', 'approved' => true],
            ['role' => 'admin', 'status' => 'suspended', 'approved' => true],
            ['role' => 'admin', 'status' => 'active', 'approved' => false],
        ] as $case) {
            $user = $this->createUser($case['role'], 'secret-pass', $case['status'], $case['approved']);
            [, $privateKey, $credentialId, $userHandle] = $this->cryptographicCredential($user);
            $challenge = random_bytes(32);

            try {
                app(PasskeyService::class)->authenticate(
                    $this->signedAssertion(
                        $privateKey,
                        $credentialId,
                        $userHandle,
                        $challenge,
                        'https://simtaqu.test',
                        0x05,
                        1
                    ),
                    $this->ceremony($challenge)
                );
                $this->fail('Akun yang tidak memenuhi scope seharusnya ditolak.');
            } catch (PasskeyException) {
                $this->assertGuest();
            }
        }
    }

    public function test_passkey_table_contains_no_biometric_columns(): void
    {
        $columns = Schema::getColumnListing('passkey_credentials');

        $this->assertContains('public_key', $columns);
        $this->assertContains('credential_id_hash', $columns);
        $this->assertNotContains('biometric', $columns);
        $this->assertNotContains('fingerprint', $columns);
        $this->assertNotContains('face', $columns);
        $this->assertNotContains('pin', $columns);
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nomor')->nullable();
            $table->string('role')->default('santri');
            $table->boolean('is_approved')->default(false);
            $table->string('account_status')->default('pending');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('santris', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('nis')->nullable();
        });

        Schema::create('profile_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('photo')->nullable();
        });

        Schema::create('institution_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('logo')->nullable();
        });

        $activityLogMigration = require database_path('migrations/2026_04_06_192609_create_activity_logs_table.php');
        $activityLogMigration->up();

        $migration = require database_path('migrations/2026_09_14_000001_create_passkey_credentials_table.php');
        $migration->up();
    }

    private function createUser(
        string $role,
        string $password = 'secret-pass',
        string $status = 'active',
        bool $approved = true
    ): User {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role).' Test',
            'email' => $role.'-'.Str::lower(Str::random(8)).'@example.test',
            'role' => $role,
            'is_approved' => $approved,
            'account_status' => $status,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function createCredential(User $user, string $name): PasskeyCredential
    {
        $credentialId = random_bytes(32);

        return $user->passkeyCredentials()->create([
            'name' => $name,
            'credential_id' => PasskeyService::base64urlEncode($credentialId),
            'credential_id_hash' => hash('sha256', $credentialId),
            'user_handle' => PasskeyService::base64urlEncode(random_bytes(32)),
            'public_key' => "-----BEGIN PUBLIC KEY-----\ninvalid-test-key\n-----END PUBLIC KEY-----\n",
            'signature_count' => 0,
            'transports' => ['internal'],
        ]);
    }

    /**
     * @return array{0: PasskeyCredential, 1: \OpenSSLAsymmetricKey, 2: string, 3: string}
     */
    private function cryptographicCredential(User $user): array
    {
        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $privateKey);

        $details = openssl_pkey_get_details($privateKey);
        $this->assertIsArray($details);
        $credentialId = random_bytes(32);
        $userHandle = random_bytes(32);

        $stored = $user->passkeyCredentials()->create([
            'name' => 'Cryptographic Test Key',
            'credential_id' => PasskeyService::base64urlEncode($credentialId),
            'credential_id_hash' => hash('sha256', $credentialId),
            'user_handle' => PasskeyService::base64urlEncode($userHandle),
            'public_key' => $details['key'],
            'signature_count' => 0,
            'transports' => ['internal'],
        ]);

        return [$stored, $privateKey, $credentialId, $userHandle];
    }

    /**
     * @return array<string, mixed>
     */
    private function signedAssertion(
        \OpenSSLAsymmetricKey $privateKey,
        string $credentialId,
        string $userHandle,
        string $challenge,
        string $origin,
        int $flags,
        int $counter
    ): array {
        $clientDataJson = json_encode([
            'type' => 'webauthn.get',
            'challenge' => PasskeyService::base64urlEncode($challenge),
            'origin' => $origin,
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $authenticatorData = hash('sha256', 'simtaqu.test', true)
            .chr($flags)
            .pack('N', $counter);
        $signedData = $authenticatorData.hash('sha256', $clientDataJson, true);
        $signature = '';
        $this->assertTrue(openssl_sign($signedData, $signature, $privateKey, OPENSSL_ALGO_SHA256));
        $encodedId = PasskeyService::base64urlEncode($credentialId);

        return [
            'id' => $encodedId,
            'rawId' => $encodedId,
            'type' => 'public-key',
            'authenticatorAttachment' => 'platform',
            'clientExtensionResults' => [],
            'response' => [
                'clientDataJSON' => PasskeyService::base64urlEncode($clientDataJson),
                'authenticatorData' => PasskeyService::base64urlEncode($authenticatorData),
                'signature' => PasskeyService::base64urlEncode($signature),
                'userHandle' => PasskeyService::base64urlEncode($userHandle),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(
        \OpenSSLAsymmetricKey $privateKey,
        string $credentialId,
        string $encodedChallenge,
        int $counter
    ): array {
        $details = openssl_pkey_get_details($privateKey);
        $this->assertIsArray($details);
        $this->assertArrayHasKey('ec', $details);

        $cosePublicKey = "\xA5"
            . "\x01\x02"
            . "\x03\x26"
            . "\x20\x01"
            . "\x21\x58\x20".$details['ec']['x']
            . "\x22\x58\x20".$details['ec']['y'];
        $authenticatorData = hash('sha256', 'simtaqu.test', true)
            . chr(0x45)
            . pack('N', $counter)
            . str_repeat("\0", 16)
            . pack('n', strlen($credentialId))
            . $credentialId
            . $cosePublicKey;
        $attestationObject = "\xA3"
            . "\x63fmt\x64none"
            . "\x67attStmt\xA0"
            . "\x68authData"
            . $this->cborByteString($authenticatorData);
        $clientDataJson = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $encodedChallenge,
            'origin' => 'https://simtaqu.test',
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $encodedId = PasskeyService::base64urlEncode($credentialId);

        return [
            'id' => $encodedId,
            'rawId' => $encodedId,
            'type' => 'public-key',
            'clientExtensionResults' => [],
            'response' => [
                'clientDataJSON' => PasskeyService::base64urlEncode($clientDataJson),
                'attestationObject' => PasskeyService::base64urlEncode($attestationObject),
                'transports' => ['internal', 'unknown-transport'],
            ],
        ];
    }

    private function cborByteString(string $value): string
    {
        $length = strlen($value);
        if ($length <= 23) {
            return chr(0x40 + $length).$value;
        }
        if ($length <= 255) {
            return "\x58".chr($length).$value;
        }

        return "\x59".pack('n', $length).$value;
    }

    /**
     * @return array<string, mixed>
     */
    private function ceremony(string $challenge): array
    {
        return [
            'challenge' => PasskeyService::base64urlEncode($challenge),
            'rp_id' => 'simtaqu.test',
            'expires_at' => now()->addMinutes(5)->timestamp,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dummyRegistrationPayload(): array
    {
        return [
            'id' => 'Y3JlZGVudGlhbA',
            'rawId' => 'Y3JlZGVudGlhbA',
            'type' => 'public-key',
            'clientExtensionResults' => [],
            'response' => [
                'clientDataJSON' => 'e30',
                'attestationObject' => 'YXR0ZXN0YXRpb24',
                'transports' => ['internal'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dummyAssertionPayload(): array
    {
        return [
            'id' => 'Y3JlZGVudGlhbA',
            'rawId' => 'Y3JlZGVudGlhbA',
            'type' => 'public-key',
            'clientExtensionResults' => [],
            'response' => [
                'clientDataJSON' => 'e30',
                'authenticatorData' => 'YXV0aGVudGljYXRvcg',
                'signature' => 'c2lnbmF0dXJl',
                'userHandle' => 'dXNlci1oYW5kbGU',
            ],
        ];
    }
}
