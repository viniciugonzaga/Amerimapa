<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MapRegion;
use App\Models\Phase;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Banco de perguntas (RN15, RN22). */
class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->only('phase_id', 'category_id', 'difficulty', 'q');
        $questions = Question::query()->with(['answerRegion', 'phase', 'category'])->withCount('answers')
            ->when($f['phase_id'] ?? null, fn ($q, $v) => $q->where('phase_id', $v))
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($f['difficulty'] ?? null, fn ($q, $v) => $q->where('difficulty', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('prompt', 'like', "%{$v}%"))
            ->orderByDesc('is_active')->orderBy('phase_id')->orderBy('id')->paginate(20)->withQueryString();

        return view('teacher.questions.index', [
            'questions' => $questions,
            'phases' => Phase::query()->orderBy('number')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'f' => $f,
        ]);
    }

    public function create()
    {
        return view('teacher.questions.form', $this->formData(new Question(['difficulty' => 'EASY', 'is_active' => true])));
    }

    public function store(Request $request)
    {
        Question::query()->create($this->validated($request));

        return redirect()->route('teacher.questions.index')->with('status', 'Pergunta criada!');
    }

    public function edit(Question $question)
    {
        return view('teacher.questions.form', $this->formData($question));
    }

    public function update(Request $request, Question $question)
    {
        $question->update($this->validated($request));

        return redirect()->route('teacher.questions.index')->with('status', 'Pergunta atualizada!');
    }

    public function destroy(Question $question)
    {
        if ($question->answers()->exists()) {
            $question->update(['is_active' => false]);

            return back()->with('status', 'A pergunta já foi respondida, então foi desativada.');
        }
        $question->delete();

        return back()->with('status', 'Pergunta removida.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'prompt' => 'required|string|max:255',
            'difficulty' => ['required', Rule::in(Question::DIFFICULTIES)],
            'answer_region_id' => 'required|integer|exists:map_regions,id',
            'phase_id' => 'required|integer|exists:phases,id',
            'category_id' => 'required|integer|exists:categories,id',
            'audio_path' => 'nullable|string|max:255',
        ], [], ['prompt' => 'enunciado', 'answer_region_id' => 'resposta correta', 'phase_id' => 'fase', 'category_id' => 'categoria']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function formData(Question $question): array
    {
        return [
            'question' => $question,
            'phases' => Phase::query()->orderBy('number')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'regions' => MapRegion::query()->with('parent')->orderBy('type')->orderBy('name')->get(),
        ];
    }
}
