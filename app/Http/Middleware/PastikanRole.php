<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanRole
{
    /**
     * Batasi akses hanya untuk role tertentu, dipakai di route:
     * Route::middleware('role:admin') atau 'role:admin,dokter'.
     */
    public function handle(Request $request, Closure $next, string ...$role): Response
    {
        if (! $request->user()?->punyaRole(...$role)) {
            abort(403, 'Akun ini tidak punya hak akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
