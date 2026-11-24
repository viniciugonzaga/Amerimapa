<?php

namespace App\Http\Middleware;

use App\Domain\Actor;
use App\Exceptions\ApiException;
use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lê o token Bearer (ou ?access_token= para EventSource) e coloca o Actor na requisição.
 * Uso: 'api.auth' (obrigatório) ou 'api.auth:optional'.
 */
class AuthenticateApi
{
    public function __construct(private readonly TokenService $tokens) {}

    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $plain = $request->bearerToken() ?: $request->query('access_token');
        $actor = $this->tokens->resolve(is_string($plain) ? $plain : null);

        if (! $actor && $mode !== 'optional') {
            throw ApiException::unauthorized($plain ? 'Token inválido ou expirado.' : 'Autenticação necessária. Envie Authorization: Bearer <token>.');
        }
        $request->attributes->set('actor', $actor);

        return $next($request);
    }

    public static function actor(Request $request): ?Actor
    {
        return $request->attributes->get('actor');
    }
}
