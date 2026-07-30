<?php

$allowedHostnames = array_values(array_filter(array_map(
    static fn (string $hostname): string => trim($hostname),
    explode(',', (string) env('TURNSTILE_ALLOWED_HOSTNAMES', ''))
)));

return [
    'login' => [
        'identity_max_attempts_per_minute' => (int) env('AUTH_LOGIN_IDENTITY_MAX_PER_MINUTE', 5),
        'ip_max_attempts_per_hour' => (int) env('AUTH_LOGIN_IP_MAX_PER_HOUR', 60),
    ],

    'registration' => [
        'minimum_form_seconds' => (int) env('AUTH_REGISTER_MIN_SECONDS', 3),
        'maximum_form_seconds' => (int) env('AUTH_REGISTER_MAX_FORM_SECONDS', 7200),
        'ip_max_attempts' => (int) env('AUTH_REGISTER_IP_MAX_ATTEMPTS', 5),
        'ip_decay_seconds' => (int) env('AUTH_REGISTER_IP_DECAY_SECONDS', 600),
        'email_max_attempts' => (int) env('AUTH_REGISTER_EMAIL_MAX_ATTEMPTS', 5),
        'email_decay_seconds' => (int) env('AUTH_REGISTER_EMAIL_DECAY_SECONDS', 3600),
    ],

    'turnstile' => [
        'enabled' => (bool) env('TURNSTILE_ENABLED', false),
        'protect_login' => (bool) env('TURNSTILE_PROTECT_LOGIN', true),
        'protect_register' => (bool) env('TURNSTILE_PROTECT_REGISTER', true),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'allowed_hostnames' => $allowedHostnames,
        'timeout_seconds' => (int) env('TURNSTILE_TIMEOUT_SECONDS', 5),
        'fail_open' => (bool) env('TURNSTILE_FAIL_OPEN', false),
    ],
];
