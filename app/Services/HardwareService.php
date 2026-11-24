<?php

namespace App\Services;

use App\Domain\Actor;
use App\Domain\AnswerFeedback;
use App\Exceptions\ApiException;
use App\Models\Device;
use App\Models\GameMatch;
use App\Models\GameMode;
use App\Models\MapRegion;

/**
 * Ponte entre o mapa físico (ESP32 + MPR121) e o jogo.
 *
 * MAPA -> SENSORES -> ESP32 -> POST /hardware/touches -> HardwareService
 *      -> MatchService (mesmas regras de uma resposta na tela) -> evento para as telas
 *      + instrução de LED/áudio de volta para o ESP32.
 *
 * RN05 pinos desconhecidos são rejeitados · RN19 pino -> região por id único
 * RN20 mapas sem heartbeat ficam offline e não podem ser vinculados a partidas.
 */
class HardwareService
{
    public function __construct(
        private readonly MatchService $matches,
        private readonly EventBus $events,
        private readonly AchievementService $achievements,
    ) {}

    public function heartbeat(string $deviceId, array $input): array
    {
        $device = $this->markSeen($deviceId, array_filter([
            'firmware_version' => $input['firmwareVersion'] ?? null,
            'sensors_ok' => $input['sensorsOk'] ?? true,
            'battery_level' => $input['batteryLevel'] ?? null,
        ], fn ($v) => $v !== null));

        if (($input['sensorsOk'] ?? true) === false) {
            $this->events->publish('device.sensor_error', $deviceId, $device->bound_match_id, [
                'message' => 'O mapa informou falha em um sensor.',
            ]);
        }

        return ['serverTime' => now()->toIso8601ZuluString('millisecond'), 'boundMatchId' => $device->bound_match_id];
    }

    public function touch(string $deviceId, string $pin): array
    {
        $device = $this->markSeen($deviceId, []);
        $region = MapRegion::query()->where('hardware_pin', $pin)->first();

        if (! $region) {
            $this->events->publish('touch.rejected', $deviceId, $device->bound_match_id, ['pin' => $pin, 'reason' => 'UNKNOWN_PIN']);
            throw ApiException::rule('UNKNOWN_PIN', "Nenhuma região cadastrada para o pino \"{$pin}\". RN05");
        }

        $info = $this->regionInfo($region);

        if ($device->bound_match_id === null) {
            $this->events->publish('touch.unbound', $deviceId, null, ['region' => $info]);

            return [
                'action' => 'NO_MATCH_BOUND',
                'region' => $info,
                'outcome' => null,
                'device' => ['led' => 'YELLOW', 'audioTrack' => null, 'message' => $region->name],
            ];
        }

        $matchId = $device->bound_match_id;
        $match = GameMatch::query()->with('mode', 'player')->find($matchId);

        if ($match && $match->mode->code === GameMode::EXPLORATION) {
            if ($match->player) {
                $this->achievements->markExplored($match->player, $region);
            }
            $this->events->publish('region.explored', $deviceId, $matchId, ['region' => $info]);

            return [
                'action' => 'EXPLORED',
                'region' => $info,
                'outcome' => null,
                'device' => ['led' => 'GREEN', 'audioTrack' => null, 'message' => $region->name],
            ];
        }

        try {
            $outcome = $this->matches->answer(Actor::device($deviceId), $matchId, $region->id, 'MAP');
        } catch (ApiException $e) {
            $this->events->publish('touch.rejected', $deviceId, $matchId, [
                'region' => $info, 'reason' => $e->errorCode, 'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->events->publish('answer.evaluated', $deviceId, $matchId, ['region' => $info, 'outcome' => $outcome]);
        if ($outcome['match']['status'] !== GameMatch::IN_PROGRESS) {
            Device::query()->whereKey($deviceId)->update(['bound_match_id' => null]);
            $this->events->publish('match.finished', $deviceId, $matchId, ['match' => $outcome['match']]);
        }

        return [
            'action' => 'ANSWERED',
            'region' => $info,
            'outcome' => $outcome,
            'device' => [
                'led' => $outcome['feedback']['led'],
                'audioTrack' => AnswerFeedback::trackNumber($outcome['feedback']['audioPath']),
                'message' => $outcome['feedback']['message'],
            ],
        ];
    }

    /** Chamado pela tela: "as respostas da partida X virão do mapa Y". */
    public function bindMatch(Actor $actor, string $deviceId, int $matchId): array
    {
        $device = Device::query()->find($deviceId);
        if (! $device || ! $device->isOnline()) {
            throw ApiException::unavailable('DEVICE_OFFLINE', "O mapa \"{$deviceId}\" não está conectado. Verifique o cabo/Wi-Fi e tente de novo. RN20");
        }

        $match = $this->matches->details($actor, $matchId); // também confere o dono
        if ($match['status'] !== GameMatch::IN_PROGRESS) {
            throw ApiException::rule('MATCH_NOT_IN_PROGRESS', 'Só partidas em andamento podem ser vinculadas a um mapa.');
        }

        // Uma partida responde por um mapa de cada vez.
        Device::query()->where('bound_match_id', $matchId)->where('device_id', '!=', $deviceId)->update(['bound_match_id' => null]);
        $device->update(['bound_match_id' => $matchId]);
        $this->events->publish('device.bound', $deviceId, $matchId);

        return $device->refresh()->toApi();
    }

    public function unbind(Actor $actor, string $deviceId): array
    {
        $device = Device::query()->find($deviceId);
        if (! $device) {
            throw ApiException::rule('UNKNOWN_DEVICE', "O mapa \"{$deviceId}\" nunca se conectou.");
        }
        $previous = $device->bound_match_id;
        if ($previous !== null && GameMatch::query()->whereKey($previous)->exists()) {
            $this->matches->details($actor, $previous); // apenas o dono ou o professor
        }
        $device->update(['bound_match_id' => null]);
        $this->events->publish('device.unbound', $deviceId, $previous);

        return $device->refresh()->toApi();
    }

    public function listStatus(): array
    {
        $this->checkConnectivity();

        return Device::query()->orderBy('device_id')->get()->map->toApi()->all();
    }

    /** RN20: avisa as telas quando um mapa para de mandar heartbeat. */
    public function checkConnectivity(): void
    {
        foreach (Device::query()->where('announced_online', true)->get() as $device) {
            if (! $device->isOnline()) {
                $device->update(['announced_online' => false]);
                $this->events->publish('device.offline', $device->device_id, $device->bound_match_id, [
                    'message' => 'O mapa foi desconectado. As respostas pelo mapa estão pausadas.',
                    'lastSeenAt' => optional($device->last_seen_at)->toIso8601ZuluString(),
                ]);
            }
        }
    }

    private function markSeen(string $deviceId, array $changes): Device
    {
        $device = Device::query()->firstOrNew(['device_id' => $deviceId]);
        $wasOnline = $device->exists && $device->announced_online && $device->isOnline();
        $device->fill($changes + ['last_seen_at' => now(), 'announced_online' => true]);
        if (! $device->name) {
            $device->name = 'Mapa '.$deviceId;
        }
        $device->save();
        if (! $wasOnline) {
            $this->events->publish('device.online', $deviceId, $device->bound_match_id, ['firmwareVersion' => $device->firmware_version]);
        }

        return $device;
    }

    private function regionInfo(MapRegion $region): array
    {
        return [
            'id' => $region->id,
            'name' => $region->name,
            'type' => $region->type,
            'isoCode' => $region->iso_code,
            'capital' => $region->capital,
            'language' => $region->language,
            'shortDescription' => $region->short_description,
            'info' => $region->info,
        ];
    }
}
