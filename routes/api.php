<?php

/*
|--------------------------------------------------------------------------
| API AmeriMapa — /api/v1
|--------------------------------------------------------------------------
| Mesmo contrato da API Node/Express do Matheus (amerimapa-api), agora em Laravel.
| 🔓 público · 🔑 token de jogador/visitante (api.auth) · 👩‍🏫 professor/admin (api.staff)
| 🛠️ admin (api.staff:admin) · 📟 ESP32 (device: X-Device-Key + X-Device-Id)
| Sucesso: { "data": ..., "meta"?: {...} } · Erro: { "error": { code, message, details? } }
*/

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GameModeController;
use App\Http\Controllers\Api\HardwareController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\PhaseController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SchoolClassController;
use Illuminate\Support\Facades\Route;

// ─── Público ────────────────────────────────────────────────────────────────
Route::get('/health', HealthController::class);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:20,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('/guest', [AuthController::class, 'guest'])->middleware('throttle:30,1');
    Route::get('/me', [AuthController::class, 'me'])->middleware('api.auth');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('api.auth');
});

Route::get('/regions', [RegionController::class, 'index']);
Route::get('/regions/pin/{pin}', [RegionController::class, 'byPin']);
Route::get('/regions/{id}', [RegionController::class, 'show'])->whereNumber('id');
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show'])->whereNumber('id');
Route::get('/phases', [PhaseController::class, 'index']);
Route::get('/phases/available', [PhaseController::class, 'available'])->middleware('api.auth:optional');
Route::get('/phases/{id}', [PhaseController::class, 'show'])->whereNumber('id');
Route::get('/game-modes', [GameModeController::class, 'index']);
Route::get('/game-modes/{id}', [GameModeController::class, 'show'])->whereNumber('id');
Route::get('/leaderboard', LeaderboardController::class);
Route::get('/achievements', [DashboardController::class, 'achievements']);
Route::get('/activities', [ActivityController::class, 'index'])->middleware('api.auth:optional');
Route::get('/activities/{id}', [ActivityController::class, 'show'])->middleware('api.auth:optional')->whereNumber('id');

// ─── ESP32 (mapa físico) ────────────────────────────────────────────────────
Route::prefix('hardware')->middleware(['device', 'throttle:600,1'])->group(function () {
    Route::post('/heartbeat', [HardwareController::class, 'heartbeat']);
    Route::post('/touches', [HardwareController::class, 'touch']);
});

// ─── Jogador ou visitante ───────────────────────────────────────────────────
Route::middleware('api.auth')->group(function () {
    Route::get('/players/{id}', [PlayerController::class, 'show'])->whereNumber('id');
    Route::patch('/players/{id}', [PlayerController::class, 'update'])->whereNumber('id');
    Route::delete('/players/{id}', [PlayerController::class, 'destroy'])->whereNumber('id');
    Route::get('/players/{id}/progress', [PlayerController::class, 'progress'])->whereNumber('id');
    Route::get('/players/{id}/matches', [PlayerController::class, 'matches'])->whereNumber('id');
    Route::get('/players/{id}/achievements', [PlayerController::class, 'achievements'])->whereNumber('id');
    Route::get('/players/{id}/summary', [PlayerController::class, 'summary'])->whereNumber('id');
    Route::get('/players/{id}/preferences', [PlayerController::class, 'preferences'])->whereNumber('id');
    Route::put('/players/{id}/preferences', [PlayerController::class, 'updatePreferences'])->whereNumber('id');

    Route::post('/regions/{id}/explore', [RegionController::class, 'explore'])->whereNumber('id');

    Route::get('/matches', [MatchController::class, 'index']);
    Route::post('/matches', [MatchController::class, 'store']);
    Route::get('/matches/{id}', [MatchController::class, 'show'])->whereNumber('id');
    Route::post('/matches/{id}/next-question', [MatchController::class, 'nextQuestion'])->whereNumber('id');
    Route::post('/matches/{id}/answers', [MatchController::class, 'answer'])->whereNumber('id');
    Route::get('/matches/{id}/answers', [MatchController::class, 'answers'])->whereNumber('id');
    Route::post('/matches/{id}/skip', [MatchController::class, 'skip'])->whereNumber('id');
    Route::post('/matches/{id}/finish', [MatchController::class, 'finish'])->whereNumber('id');

    Route::post('/hardware/bind-match', [HardwareController::class, 'bindMatch']);
    Route::post('/hardware/unbind', [HardwareController::class, 'unbind']);
    Route::get('/hardware/status', [HardwareController::class, 'status']);
    Route::get('/hardware/events/poll', [HardwareController::class, 'poll']);
    Route::get('/hardware/events', [HardwareController::class, 'events']);

    // ─── Professor / admin ──────────────────────────────────────────────────
    Route::middleware('api.staff')->group(function () {
        Route::get('/players', [PlayerController::class, 'index']);
        Route::post('/players', [PlayerController::class, 'store']);

        Route::post('/regions', [RegionController::class, 'store']);
        Route::patch('/regions/{id}', [RegionController::class, 'update'])->whereNumber('id');
        Route::delete('/regions/{id}', [RegionController::class, 'destroy'])->whereNumber('id');

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{id}', [CategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->whereNumber('id');

        Route::post('/phases', [PhaseController::class, 'store']);
        Route::patch('/phases/{id}', [PhaseController::class, 'update'])->whereNumber('id');
        Route::delete('/phases/{id}', [PhaseController::class, 'destroy'])->whereNumber('id');

        Route::get('/questions', [QuestionController::class, 'index']);
        Route::post('/questions', [QuestionController::class, 'store']);
        Route::get('/questions/{id}', [QuestionController::class, 'show'])->whereNumber('id');
        Route::patch('/questions/{id}', [QuestionController::class, 'update'])->whereNumber('id');
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy'])->whereNumber('id');

        Route::post('/game-modes', [GameModeController::class, 'store']);
        Route::patch('/game-modes/{id}', [GameModeController::class, 'update'])->whereNumber('id');
        Route::delete('/game-modes/{id}', [GameModeController::class, 'destroy'])->whereNumber('id');

        Route::post('/activities', [ActivityController::class, 'store']);
        Route::patch('/activities/{id}', [ActivityController::class, 'update'])->whereNumber('id');
        Route::delete('/activities/{id}', [ActivityController::class, 'destroy'])->whereNumber('id');

        Route::get('/classes', [SchoolClassController::class, 'index']);
        Route::post('/classes', [SchoolClassController::class, 'store']);
        Route::get('/classes/{id}', [SchoolClassController::class, 'show'])->whereNumber('id');
        Route::patch('/classes/{id}', [SchoolClassController::class, 'update'])->whereNumber('id');
        Route::delete('/classes/{id}', [SchoolClassController::class, 'destroy'])->whereNumber('id');

        Route::get('/dashboard/teacher', [DashboardController::class, 'teacher']);

        Route::delete('/matches/{id}', [MatchController::class, 'destroy'])->middleware('api.staff:admin')->whereNumber('id');
    });
});
