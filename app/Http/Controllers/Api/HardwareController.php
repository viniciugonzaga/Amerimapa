<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\GameEvent;
use App\Services\HardwareService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Integração com o mapa físico (ESP32 + MPR121 + DFPlayer). RN05, RN19, RN20 */
class HardwareController extends ApiController
{
    public function __construct(private readonly HardwareService $hardware) {}

    /** [ESP32] "Estou vivo" — enviar a cada ~5 s. */
    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'firmwareVersion' => 'sometimes|string|max:40',
            'sensorsOk' => 'sometimes|boolean',
            'batteryLevel' => 'sometimes|integer|min:0|max:100',
        ]);

        return $this->ok($this->hardware->heartbeat($request->attributes->get('deviceId'), $data));
    }

    /** [ESP32] Um ponto de cobre foi tocado. RN20: payload incompleto vira 400 e nunca resposta. */
    public function touch(Request $request)
    {
        $data = $request->validate(['pin' => ['required', 'string', 'regex:/^[A-Za-z0-9:_-]{1,20}$/']]);

        return $this->ok($this->hardware->touch($request->attributes->get('deviceId'), trim($data['pin'])));
    }

    public function bindMatch(Request $request)
    {
        $data = $request->validate(['deviceId' => 'required|string|max:64', 'matchId' => 'required|integer|min:1']);

        return $this->ok($this->hardware->bindMatch($this->actor($request), $data['deviceId'], (int) $data['matchId']));
    }

    public function unbind(Request $request)
    {
        $data = $request->validate(['deviceId' => 'required|string|max:64']);

        return $this->ok($this->hardware->unbind($this->actor($request), $data['deviceId']));
    }

    public function status()
    {
        return $this->ok($this->hardware->listStatus());
    }

    /**
     * Polling (usado pelas telas): eventos com id > after. Também verifica mapas que caíram (RN20).
     * GET /hardware/events/poll?deviceId=map-01&matchId=12&after=0
     */
    public function poll(Request $request)
    {
        $f = $request->validate(['deviceId' => 'sometimes|nullable|string|max:64', 'matchId' => 'sometimes|nullable|integer', 'after' => 'sometimes|integer|min:0']);
        $actor = $this->actor($request);
        if (empty($f['deviceId']) && empty($f['matchId']) && ! $actor->isStaff()) {
            throw ApiException::badRequest('Informe deviceId ou matchId.');
        }
        $this->hardware->checkConnectivity();

        if (! array_key_exists('after', $f)) {
            // primeira chamada (sem "after"): devolve o id do evento mais recente
            return $this->ok([], ['lastEventId' => (int) GameEvent::query()->max('id')]);
        }
        $after = (int) $f['after'];
        $events = $this->query($f, $after)->limit(50)->get();

        return $this->ok($events->map->toApi(), ['lastEventId' => (int) ($events->last()->id ?? $after)]);
    }

    /**
     * Server-Sent Events (compatível com a API Node). Mantém a conexão ~25 s e encerra;
     * o EventSource reconecta sozinho usando Last-Event-ID.
     */
    public function events(Request $request): StreamedResponse
    {
        $f = $request->validate(['deviceId' => 'sometimes|nullable|string|max:64', 'matchId' => 'sometimes|nullable|integer']);
        $actor = $this->actor($request);
        if (empty($f['deviceId']) && empty($f['matchId']) && ! $actor->isStaff()) {
            throw ApiException::badRequest('Informe deviceId.');
        }
        $after = (int) ($request->header('Last-Event-ID') ?: GameEvent::query()->max('id'));

        return response()->stream(function () use ($f, $after) {
            echo "retry: 1000\nevent: connected\ndata: ".json_encode(['deviceId' => $f['deviceId'] ?? null])."\n\n";
            @ob_flush();
            flush();
            $until = microtime(true) + 25;
            while (microtime(true) < $until && ! connection_aborted()) {
                $this->hardware->checkConnectivity();
                foreach ($this->query($f, $after)->limit(50)->get() as $event) {
                    $after = $event->id;
                    echo "id: {$event->id}\nevent: {$event->type}\ndata: ".json_encode($event->toApi(), JSON_UNESCAPED_UNICODE)."\n\n";
                }
                echo ": keep-alive\n\n";
                @ob_flush();
                flush();
                usleep(500_000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function query(array $f, int $after)
    {
        return GameEvent::query()->where('id', '>', $after)->orderBy('id')
            ->when(! empty($f['deviceId']) || ! empty($f['matchId']), fn ($q) => $q->where(function ($w) use ($f) {
                if (! empty($f['deviceId'])) {
                    $w->orWhere('device_id', $f['deviceId']);
                }
                if (! empty($f['matchId'])) {
                    $w->orWhere('match_id', $f['matchId']);
                }
            }));
    }
}
