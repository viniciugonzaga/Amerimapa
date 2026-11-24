<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Perguntas (contêm a resposta, por isso só professor/admin). RN15 */
class QuestionController extends ApiController
{
    private const MAP = ['answerRegionId' => 'answer_region_id', 'phaseId' => 'phase_id', 'categoryId' => 'category_id', 'audioPath' => 'audio_path', 'isActive' => 'is_active'];

    public function index(Request $request)
    {
        [$page, $limit] = $this->pagination($request);
        $f = $request->validate([
            'phaseId' => 'sometimes|integer', 'categoryId' => 'sometimes|integer', 'answerRegionId' => 'sometimes|integer',
            'difficulty' => ['sometimes', Rule::in(Question::DIFFICULTIES)], 'isActive' => 'sometimes|in:true,false,1,0',
            'search' => 'sometimes|string|max:100',
        ]);
        $q = Question::query()->orderBy('phase_id')->orderBy('id');
        foreach (['phaseId' => 'phase_id', 'categoryId' => 'category_id', 'answerRegionId' => 'answer_region_id', 'difficulty' => 'difficulty'] as $in => $col) {
            if (isset($f[$in])) {
                $q->where($col, $f[$in]);
            }
        }
        if (isset($f['isActive'])) {
            $q->where('is_active', in_array($f['isActive'], ['true', '1'], true));
        }
        if (isset($f['search'])) {
            $q->where('prompt', 'like', "%{$f['search']}%");
        }
        $total = (clone $q)->count();

        return $this->ok($q->forPage($page, $limit)->get()->map->toApi(), $this->meta($page, $limit, $total));
    }

    public function show(int $id)
    {
        return $this->ok($this->find($id)->toApi());
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules('required'));

        return $this->created(Question::query()->create($this->snake($data, self::MAP))->toApi());
    }

    public function update(Request $request, int $id)
    {
        $question = $this->find($id);
        $data = $request->validate($this->rules('sometimes'));
        $this->requireAny($data);
        $question->update($this->snake($data, self::MAP));

        return $this->ok($question->refresh()->toApi());
    }

    /** Pergunta já respondida é desativada (mantém o histórico); senão é apagada. */
    public function destroy(int $id)
    {
        $question = $this->find($id);
        if ($question->answers()->exists()) {
            $question->update(['is_active' => false]);
        } else {
            $question->delete();
        }

        return $this->noContent();
    }

    private function rules(string $req): array
    {
        return [
            'prompt' => "{$req}|string|max:255",
            'difficulty' => [$req, Rule::in(Question::DIFFICULTIES)],
            'answerRegionId' => "{$req}|integer|exists:map_regions,id",
            'phaseId' => "{$req}|integer|exists:phases,id",
            'categoryId' => "{$req}|integer|exists:categories,id",
            'audioPath' => 'sometimes|nullable|string|max:255',
            'isActive' => 'sometimes|boolean',
        ];
    }

    private function find(int $id): Question
    {
        return Question::query()->find($id) ?? throw ApiException::notFound('Pergunta', $id);
    }
}
