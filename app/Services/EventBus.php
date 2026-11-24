<?php

namespace App\Services;

use App\Models\GameEvent;

/**
 * Publica eventos de jogo numa tabela; as telas leem por polling
 * (GET /hardware/events/poll) ou SSE (GET /hardware/events).
 */
class EventBus
{
    public function publish(string $type, ?string $deviceId, ?int $matchId, array $payload = []): GameEvent
    {
        if (random_int(1, 200) === 1) {
            GameEvent::query()->where('created_at', '<', now()->subDay())->delete();
        }

        return GameEvent::query()->create([
            'type' => $type,
            'device_id' => $deviceId,
            'match_id' => $matchId,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
