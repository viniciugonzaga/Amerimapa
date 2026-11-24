<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** ESP32: headers X-Device-Key (chave compartilhada) e X-Device-Id (nome do mapa). */
class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('X-Device-Key');
        $id = trim((string) $request->header('X-Device-Id'));

        if ($key === '' || ! hash_equals((string) config('amerimapa.device_key'), $key)) {
            throw ApiException::unauthorized('Chave do dispositivo inválida.');
        }
        if ($id === '' || ! preg_match('/^[A-Za-z0-9._:-]{1,64}$/', $id)) {
            throw ApiException::badRequest('Header X-Device-Id ausente ou inválido.');
        }
        $request->attributes->set('deviceId', $id);

        return $next($request);
    }
}
