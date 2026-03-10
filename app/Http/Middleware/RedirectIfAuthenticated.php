<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
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
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // 1. Jika belum di-approve (kecuali superadmin/admin) arahkan ke halaman pending
                if (!$user->is_approved && !in_array($user->role, ['superadmin', 'admin'])) {
                    // Pastikan Mas punya route 'pending' atau arahkan ke halaman pemberitahuan
                    // return redirect()->route('pending');
                    // Sementara kita arahkan ke halaman logout atau home dengan pesan
                    Auth::logout();
                    return redirect()->route('waiting.approval');
                }

                // 2. Redirect dinamis berdasarkan Role
                switch ($user->role) {
                    case 'superadmin':
                        return redirect('/superadmin/dashboard');
                    case 'admin':
                        return redirect('/admin/dashboard');
                    case 'musyrif':
                        return redirect('/musyrif/dashboard');
                    case 'santri':
                        return redirect('/santri/dashboard');
                    default:
                        return redirect('/login');
                }
            }
        }

        return $next($request);
    }
}
