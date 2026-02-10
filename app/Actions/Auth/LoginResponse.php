<?php

namespace App\Actions\Auth;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        // Redirect berdasarkan role
        $redirectTo = match ($user->role) {
            'superadmin' => route('superadmin.dashboard'),
            'admin' => route('admin.dashboard'),
            'musyrif' => route('musyrif.dashboard'),
            'santri' => route('santri.dashboard'),
            'pimpinan' => route('admin.laporan.index'), // misal pimpinan lihat laporan saja
            default => '/dashboard',                  // fallback
        };

        return redirect()->intended($redirectTo)
            ->with('success', 'Login berhasil! Selamat datang kembali, ' . $user->name . '.');
    }
}
