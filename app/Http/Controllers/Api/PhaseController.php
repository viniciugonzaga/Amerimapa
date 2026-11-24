<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\Phase;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Fases. RN02, RN11 */
class PhaseController extends ApiController
{
    private const MAP = ['minUnlockScore' => 'min_unlock_score'];

    public function index()
    {
        return $this->ok(Phase::query()->withCount('questions')->orderBy('number')->get()
            ->map(fn (Phase $p) => $p->toApi() + ['questionsCount' => $p->questions_count]));
    }

    /** Fases com status de bloqueio para quem pergunta (token opcional). */
    public function available(Request $request, ProgressService $progress)
    {
        return $this->ok(array_map([ProgressService::class, 'phaseProgressToApi'], $progress->availablePhases($this->optionalActor($request))));
    }

    public function show(int $id)
    {
        return $this->ok($this->find($id)->toApi());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'number' => 'required|integer|min:1|unique:phases,number',
            'title' => 'required|string|max:120',
            'description' => 'sometimes|nullable|string|max:255',
            'minUnlockScore' => 'sometimes|integer|min:0',
        ]);

        return $this->created(Phase::query()->create($this->snake($data, self::MAP))->toApi());
    }

    public function update(Request $request, int $id)
    {
        $phase = $this->find($id);
        $data = $request->validate([
            'number' => ['sometimes', 'integer', 'min:1', Rule::unique('phases', 'number')->ignore($id)],
            'title' => 'sometimes|string|max:120',
            'description' => 'sometimes|nullable|string|max:255',
            'minUnlockScore' => 'sometimes|integer|min:0',
        ]);
        $this->requireAny($data);
        $phase->update($this->snake($data, self::MAP));

        return $this->ok($phase->refresh()->toApi());
    }

    public function destroy(int $id)
    {
        $phase = $this->find($id);
        if ($phase->questions()->exists()) {
            throw ApiException::conflict('A fase possui perguntas.', 'PHASE_IN_USE');
        }
        $phase->delete();

        return $this->noContent();
    }

    private function find(int $id): Phase
    {
        return Phase::query()->find($id) ?? throw ApiException::notFound('Fase', $id);
    }
}
