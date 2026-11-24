<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Telas web: 'role:staff' (professor/admin) ou 'role:student'. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $ok = $role === 'staff' ? $user?->isStaff() : $user?->isStudent();
        if (! $ok) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
