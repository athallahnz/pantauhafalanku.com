<?php

namespace App\Actions\Auth;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        // Pimpinan cannot access admin routes; discard a stale intended admin URL.
        $intendedPath = parse_url((string) $request->session()->get('url.intended', ''), PHP_URL_PATH);
        if ($user->role === 'pimpinan' && is_string($intendedPath)
            && ($intendedPath === '/admin' || str_starts_with($intendedPath, '/admin/'))) {
            $request->session()->forget('url.intended');
        }

        // Redirect berdasarkan role
        $redirectTo = match ($user->role) {
            'superadmin' => route('superadmin.dashboard'),
            'admin' => route('admin.dashboard'),
            'musyrif' => route('musyrif.dashboard'),
            'santri' => route('santri.dashboard'),
            'pimpinan' => route('pimpinan.dashboard'),
            default => '/dashboard',
        };

        return redirect()->intended($redirectTo)
            ->with('success', 'Login berhasil! Selamat datang kembali, ' . $user->name . '.');
    }
}
