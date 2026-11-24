<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\EnsurePlayerOrGuest;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\RequireStaff;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.auth' => AuthenticateApi::class,
            'api.staff' => RequireStaff::class,
            'device' => AuthenticateDevice::class,
            'role' => EnsureRole::class,
            'player.or.guest' => EnsurePlayerOrGuest::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request) => $request->is('api/*');

        // Formato de erro igual ao da API do Matheus: { "error": { code, message, details? } }
        $exceptions->render(function (ApiException $e, Request $request) {
            return response()->json(['error' => $e->toArray()], $e->status);
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }
            $details = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $details[] = ['field' => $field, 'message' => $message];
                }
            }

            return response()->json(['error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'A requisição contém dados inválidos.',
                'details' => $details,
            ]], 400);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Autenticação necessária.']], 401)
                : null;
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Recurso não encontrado.']], 404)
                : null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Recurso não encontrado.']], 404)
                : null;
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            return $isApi($request)
                ? response()->json(['error' => ['code' => 'HTTP_'.$e->getStatusCode(), 'message' => $e->getMessage() ?: 'Erro na requisição.']], $e->getStatusCode())
                : null;
        });

        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $isApi($request) || $request->expectsJson());
    })->create();
