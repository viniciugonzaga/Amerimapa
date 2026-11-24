<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\GameMode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Modos de jogo. RN08, RN09, RN12 */
class GameModeController extends ApiController
{
    private const MAP = ['isTimed' => 'is_timed', 'timeLimitSeconds' => 'time_limit_seconds', 'maxAttempts' => 'max_attempts', 'questionsPerMatch' => 'questions_per_match', 'isScored' => 'is_scored', 'speedBonus' => 'speed_bonus', 'streakBonus' => 'streak_bonus'];

    public function index()
    {
        return $this->ok(GameMode::query()->orderBy('id')->get()->map->toApi());
    }

    public function show(int $id)
    {
        return $this->ok($this->find($id)->toApi());
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules('required'));

        return $this->created(GameMode::query()->create($this->snake($data, self::MAP))->toApi());
    }

    public function update(Request $request, int $id)
    {
        $mode = $this->find($id);
        $data = $request->validate($this->rules('sometimes', $id));
        $this->requireAny($data);
        $mode->update($this->snake($data, self::MAP));

        return $this->ok($mode->refresh()->toApi());
    }

    public function destroy(int $id)
    {
        $mode = $this->find($id);
        if (\App\Models\GameMatch::query()->where('mode_id', $id)->exists()) {
            throw ApiException::conflict('Há partidas com este modo.', 'MODE_IN_USE');
        }
        $mode->delete();

        return $this->noContent();
    }

    private function rules(string $req, ?int $id = null): array
    {
        return [
            'code' => [$req, Rule::in(['EXPLORATION', 'TRAINING', 'QUIZ', 'CHALLENGE', 'CLASSROOM']), Rule::unique('game_modes', 'code')->ignore($id)],
            'name' => "{$req}|string|max:80",
            'description' => 'sometimes|nullable|string|max:255',
            'isTimed' => 'sometimes|boolean',
            'timeLimitSeconds' => 'sometimes|nullable|integer|min:5|max:600',
            'maxAttempts' => 'sometimes|nullable|integer|min:1|max:10',
            'questionsPerMatch' => 'sometimes|nullable|integer|min:1|max:100',
            'isScored' => 'sometimes|boolean',
            'speedBonus' => 'sometimes|boolean',
            'streakBonus' => 'sometimes|boolean',
        ];
    }

    private function find(int $id): GameMode
    {
        return GameMode::query()->find($id) ?? throw ApiException::notFound('Modo de jogo', $id);
    }
}
