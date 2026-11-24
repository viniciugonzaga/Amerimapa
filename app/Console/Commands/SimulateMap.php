<?php

namespace App\Console\Commands;

use App\Exceptions\ApiException;
use App\Models\Device;
use App\Models\GameMatch;
use App\Models\MapRegion;
use App\Services\HardwareService;
use Illuminate\Console\Command;

/**
 * Simula o mapa físico (ESP32) sem hardware: envia heartbeats e toques
 * pelo mesmo HardwareService usado por POST /hardware/heartbeat e /hardware/touches.
 *
 *   php artisan amerimapa:mapa            -> modo interativo (digite um pino, ISO ou nome do país)
 *   php artisan amerimapa:mapa --auto     -> responde sozinho às perguntas (acerta ~75%)
 */
class SimulateMap extends Command
{
    protected $signature = 'amerimapa:mapa {--device= : Id do mapa (padrão: DEFAULT_DEVICE_ID)} {--auto : Responde automaticamente} {--acerto=75 : % de acerto no modo automático}';

    protected $description = 'Simula o mapa tátil (ESP32) para testar o jogo sem hardware';

    public function handle(HardwareService $hardware): int
    {
        $device = $this->option('device') ?: config('amerimapa.default_device_id');
        $hardware->heartbeat($device, ['firmwareVersion' => 'simulador', 'sensorsOk' => true, 'batteryLevel' => 100]);
        $this->info("Mapa simulado \"{$device}\" conectado. Abra uma atividade no navegador. Ctrl+C para sair.");

        return $this->option('auto') ? $this->auto($hardware, $device) : $this->interactive($hardware, $device);
    }

    private function auto(HardwareService $hardware, string $device): int
    {
        $rate = max(0, min(100, (int) $this->option('acerto')));
        $lastQuestion = null;
        while (true) {
            $hardware->heartbeat($device, ['firmwareVersion' => 'simulador', 'sensorsOk' => true, 'batteryLevel' => 100]);
            $bound = Device::query()->find($device)?->bound_match_id;
            $match = $bound ? GameMatch::query()->with('activeQuestion.answerRegion')->find($bound) : null;
            $question = $match?->activeQuestion;
            $key = $question ? $match->id.'-'.$question->id.'-'.$match->attempts_on_question : null;
            if ($question && $key !== $lastQuestion) {
                sleep(3);
                $lastQuestion = $key;
                $target = random_int(1, 100) <= $rate
                    ? $this->pinFor($question->answerRegion)
                    : MapRegion::query()->whereNotNull('hardware_pin')->where('id', '!=', $question->answer_region_id)->inRandomOrder()->value('hardware_pin');
                $this->touch($hardware, $device, $target, $question->prompt);
            }
            sleep(2);
        }
    }

    private function interactive(HardwareService $hardware, string $device): int
    {
        $this->line('Digite um pino (5B:11), código ISO (BRA) ou nome do país (Brasil). Linha vazia = só heartbeat.');
        while (true) {
            $hardware->heartbeat($device, ['firmwareVersion' => 'simulador', 'sensorsOk' => true, 'batteryLevel' => 100]);
            $input = trim((string) $this->ask('Toque'));
            if ($input === '') {
                continue;
            }
            $region = MapRegion::query()->where('hardware_pin', $input)->orWhere('iso_code', strtoupper($input))->orWhere('name', $input)->first();
            $this->touch($hardware, $device, $region ? $this->pinFor($region) : $input, null);
        }
    }

    /** Uma região sem pino (ex.: América do Sul) é tocada por um dos países dela. */
    private function pinFor(MapRegion $region): ?string
    {
        return $region->hardware_pin ?? MapRegion::query()->whereIn('id', $region->subtreeIds())->whereNotNull('hardware_pin')->inRandomOrder()->value('hardware_pin');
    }

    private function touch(HardwareService $hardware, string $device, ?string $pin, ?string $prompt): void
    {
        if (! $pin) {
            $this->warn('Região sem pino cadastrado.');

            return;
        }
        try {
            $r = $hardware->touch($device, $pin);
            $this->line(($prompt ? "[{$prompt}] " : '')."toque {$pin} → {$r['region']['name']} · {$r['action']} · LED {$r['device']['led']} · {$r['device']['message']}");
        } catch (ApiException $e) {
            $this->error("{$e->errorCode}: {$e->getMessage()}");
        }
    }
}
