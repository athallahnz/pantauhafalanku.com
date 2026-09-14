<?php

$applicationUrl = (string) env('APP_URL', 'http://localhost');
$scheme = strtolower((string) parse_url($applicationUrl, PHP_URL_SCHEME)) ?: 'http';
$host = strtolower((string) parse_url($applicationUrl, PHP_URL_HOST)) ?: 'localhost';
$port = parse_url($applicationUrl, PHP_URL_PORT);
$formattedHost = str_contains($host, ':') ? '['.$host.']' : $host;
$defaultPort = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
$defaultOrigin = $scheme.'://'.$formattedHost.($port && ! $defaultPort ? ':'.$port : '');

$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', (string) env('PASSKEY_ALLOWED_ORIGINS', $defaultOrigin))
)));

return [
    'rp_name' => env('PASSKEY_RP_NAME', env('APP_NAME', 'SIMTAQU')),
    'rp_id' => env('PASSKEY_RP_ID', $host),
    'allowed_origins' => $allowedOrigins,

    // Nilai-nilai keamanan ini sengaja tidak dapat diturunkan lewat environment.
    'user_verification' => 'required',
    'resident_key' => 'required',
    'attestation' => 'none',

    'timeout_seconds' => 60,
    'ceremony_ttl_seconds' => 300,
    'login_attempts_per_minute' => 10,
    'management_attempts_per_minute' => 10,

    'allowed_roles' => [
        'superadmin',
        'admin',
        'pimpinan',
        'musyrif',
    ],
];
