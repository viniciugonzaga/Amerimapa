<?php

namespace App\Services;

use App\Domain\Actor;
use App\Models\AccessToken;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Tokens Bearer opacos (equivalente ao JWT da API Node). RN01: visitante ou perfil cadastrado.
 */
class TokenService
{
    public function issueForPlayer(User $player): string
    {
        return $this->issue(['player_id' => $player->id]);
    }

    /** @return array{0:string,1:string} [token, guestId] */
    public function issueForGuest(?string $guestId = null): array
    {
        $guestId ??= (string) Str::uuid();

        return [$this->issue(['guest_id' => $guestId]), $guestId];
    }

    public function resolve(?string $plain): ?Actor
    {
        if (! $plain) {
            return null;
        }
        $token = AccessToken::query()->where('token_hash', hash('sha256', $plain))->first();
        if (! $token || ($token->expires_at && $token->expires_at->isPast())) {
            return null;
        }
        if (! $token->last_used_at || $token->last_used_at->diffInMinutes(now(), true) >= 5) {
            $token->forceFill(['last_used_at' => now()])->save();
        }
        if ($token->player_id) {
            $player = User::query()->find($token->player_id);

            return $player ? Actor::player($player) : null;
        }

        return $token->guest_id ? Actor::guest($token->guest_id) : null;
    }

    public function revoke(?string $plain): void
    {
        if ($plain) {
            AccessToken::query()->where('token_hash', hash('sha256', $plain))->delete();
        }
    }

    private function issue(array $owner): string
    {
        $plain = Str::random(64);
        AccessToken::query()->create($owner + [
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays(config('amerimapa.token_ttl_days')),
            'created_at' => now(),
        ]);
        // limpeza simples de tokens vencidos
        if (random_int(1, 50) === 1) {
            AccessToken::query()->where('expires_at', '<', now())->delete();
        }

        return $plain;
    }
}
