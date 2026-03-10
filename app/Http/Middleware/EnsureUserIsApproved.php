<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && !auth()->user()->is_approved) {
            // Jika belum di-approve, jangan kasih pesan error di login,
            // tapi arahkan ke halaman waiting agar lebih ramah.
            auth()->logout();
            return redirect()->route('waiting.approval');
        }

        return $next($request);
    }
}
