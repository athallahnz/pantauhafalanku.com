<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $status = (string) ($user->account_status ?? 'pending');
        $isActive = $status === 'active' && (bool) $user->is_approved;

        if ($isActive) {
            return $next($request);
        }

        $message = match ($status) {
            'pending' => 'Akun Anda masih menunggu persetujuan.',
            'suspended' => 'Akun Anda sedang ditangguhkan. Hubungi Super Admin untuk informasi lebih lanjut.',
            'rejected' => 'Permohonan akun Anda ditolak. Hubungi administrator apabila diperlukan.',
            'archived' => 'Akun Anda telah diarsipkan dan tidak dapat digunakan.',
            default => 'Akun Anda masih menunggu persetujuan administrator.',
        };

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($status === 'pending' && Route::has('waiting.approval')) {
            return redirect()->route('waiting.approval')->with('error', $message);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
