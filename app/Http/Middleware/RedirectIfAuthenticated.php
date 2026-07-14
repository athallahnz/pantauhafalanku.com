<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (!Auth::guard($guard)->check()) {
                continue;
            }

            $user = Auth::guard($guard)->user();
            $status = (string) (
                $user->account_status
                ?: ((bool) $user->is_approved ? 'active' : 'pending')
            );

            if ($status !== 'active' || !(bool) $user->is_approved) {
                $message = match ($status) {
                    'suspended' => 'Akun Anda sedang ditangguhkan.',
                    'rejected' => 'Permohonan akun Anda telah ditolak.',
                    'archived' => 'Akun Anda telah diarsipkan.',
                    default => 'Akun Anda masih menunggu persetujuan.',
                };

                Auth::guard($guard)->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($status === 'pending') {
                    return redirect()->route('waiting.approval')->with('error', $message);
                }

                return redirect()->route('login')->with('error', $message);
            }

            $dashboardRoute = match ((string) $user->role) {
                'superadmin' => 'superadmin.dashboard',
                'pimpinan' => 'pimpinan.dashboard',
                'admin' => 'admin.dashboard',
                'musyrif' => 'musyrif.dashboard',
                'santri' => 'santri.dashboard',
                default => null,
            };

            if ($dashboardRoute && app('router')->has($dashboardRoute)) {
                return redirect()->route($dashboardRoute);
            }

            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Role akun tidak dikenali oleh sistem.',
            ]);
        }

        return $next($request);
    }
}
