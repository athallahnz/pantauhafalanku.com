<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Contoh penggunaan:
     * ->middleware('role:superadmin')
     * ->middleware('role:superadmin,admin')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $allowedRoles = collect(explode('|', $roles))
            ->map(fn($r) => strtolower(trim($r)))
            ->filter()
            ->toArray();

        $userRole = strtolower(trim((string) $request->user()->role));

        if (!in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }

}
