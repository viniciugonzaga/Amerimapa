<?php

namespace App\Http\Middleware;

use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telas de jogo: aceita usuário logado ou visitante (RN01).
 * Garante um token de API na sessão para o JavaScript das telas.
 */
class EnsurePlayerOrGuest
{
    public function __construct(private readonly TokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $user = $request->user();

        if ($user) {
            if (! $session->get('api_token') || ! $this->tokens->resolve($session->get('api_token'))?->isPlayer()) {
                $session->put('api_token', $this->tokens->issueForPlayer($user));
            }
        } elseif ($session->get('guest_id')) {
            if (! $this->tokens->resolve($session->get('api_token'))) {
                [$token] = $this->tokens->issueForGuest($session->get('guest_id'));
                $session->put('api_token', $token);
            }
        } else {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
