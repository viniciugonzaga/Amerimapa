<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Rotas só para professor/admin ('api.staff') ou só admin ('api.staff:admin'). */
class RequireStaff
{
    public function handle(Request $request, Closure $next, string $level = 'staff'): Response
    {
        $actor = AuthenticateApi::actor($request);
        $allowed = $level === 'admin' ? $actor?->isAdmin() : $actor?->isStaff();
        if (! $allowed) {
            throw ApiException::forbidden($level === 'admin' ? 'Apenas administradores.' : 'Apenas professores ou administradores.');
        }

        return $next($request);
    }
}
