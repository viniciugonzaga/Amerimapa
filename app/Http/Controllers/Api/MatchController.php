<?php

namespace App\Http\Controllers\Api;

use App\Models\GameMatch;
use App\Services\MatchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Partidas — o fluxo principal do jogo. */
class MatchController extends ApiController
{
    public function __construct(private readonly MatchService $matches) {}

    public function index(Request $request)
    {
        [$page, $limit] = $this->pagination($request);
        $f = $request->validate([
            'status' => ['sometimes', Rule::in([GameMatch::IN_PROGRESS, GameMatch::COMPLETED, GameMatch::ABANDONED])],
            'modeId' => 'sometimes|integer', 'phaseId' => 'sometimes|integer',
            'playerId' => 'sometimes|integer', 'activityId' => 'sometimes|integer',
        ]);
        [$items, $total] = $this->matches->list($this->actor($request), $f, $page, $limit);

        return $this->ok($items->map->toApi(), $this->meta($page, $limit, $total));
    }

    /** RN02: modo + fase (ou uma atividade, que já define os dois). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'modeId' => 'sometimes|nullable|integer|min:1',
            'phaseId' => 'sometimes|nullable|integer|min:1',
            'activityId' => 'sometimes|nullable|integer|min:1',
        ]);

        return $this->created($this->matches->start($this->actor($request), $data));
    }

    public function show(Request $request, int $id)
    {
        return $this->ok($this->matches->details($this->actor($request), $id));
    }

    public function nextQuestion(Request $request, int $id)
    {
        return $this->ok($this->matches->nextQuestion($this->actor($request), $id));
    }

    /** RN04: a resposta é a região tocada no mapa (ou clicada na tela). */
    public function answer(Request $request, int $id)
    {
        $data = $request->validate([
            'regionId' => 'required|integer|min:1',
            'source' => ['sometimes', Rule::in(['MAP', 'SCREEN'])],
        ]);

        return $this->ok($this->matches->answer($this->actor($request), $id, (int) $data['regionId'], $data['source'] ?? 'SCREEN'));
    }

    public function answers(Request $request, int $id)
    {
        return $this->ok($this->matches->listAnswers($this->actor($request), $id)->map->toApi());
    }

    public function skip(Request $request, int $id)
    {
        return $this->ok($this->matches->skip($this->actor($request), $id));
    }

    public function finish(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['sometimes', Rule::in([GameMatch::COMPLETED, GameMatch::ABANDONED])]]);

        return $this->ok($this->matches->finish($this->actor($request), $id, $data['status'] ?? GameMatch::COMPLETED));
    }

    public function destroy(int $id)
    {
        $this->matches->remove($id);

        return $this->noContent();
    }
}
