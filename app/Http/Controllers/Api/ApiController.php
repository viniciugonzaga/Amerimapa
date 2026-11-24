<?php

namespace App\Http\Controllers\Api;

use App\Domain\Actor;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base dos controllers da API. Resposta de sucesso: { "data": ..., "meta"?: {...} }.
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data, ?array $meta = null, int $status = 200): JsonResponse
    {
        $body = ['data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function created(mixed $data): JsonResponse
    {
        return $this->ok($data, null, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function actor(Request $request): Actor
    {
        $actor = AuthenticateApi::actor($request);
        if (! $actor) {
            throw ApiException::unauthorized();
        }

        return $actor;
    }

    protected function optionalActor(Request $request): ?Actor
    {
        return AuthenticateApi::actor($request);
    }

    /** @return array{0:int,1:int} [page, limit] */
    protected function pagination(Request $request): array
    {
        $data = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        return [(int) ($data['page'] ?? 1), (int) ($data['limit'] ?? 20)];
    }

    protected function meta(int $page, int $limit, int $total): array
    {
        return ['page' => $page, 'limit' => $limit, 'total' => $total, 'totalPages' => max(1, (int) ceil($total / $limit))];
    }

    /** Exige ao menos um campo no PATCH (como o nonEmpty() do Zod). */
    protected function requireAny(array $data): void
    {
        if ($data === []) {
            throw ApiException::badRequest('Envie ao menos um campo para atualizar.');
        }
    }

    /** camelCase -> snake_case nas chaves validadas. */
    protected function snake(array $data, array $map): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[$map[$key] ?? $key] = $value;
        }

        return $out;
    }
}
