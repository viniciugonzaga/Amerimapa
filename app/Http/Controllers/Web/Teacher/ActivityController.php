<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\GameMatch;
use App\Models\GameMode;
use App\Models\MapRegion;
use App\Models\Phase;
use App\Models\Question;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Atividades do mapa: o professor escolhe fase, modo, turma e as perguntas. */
class ActivityController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $classIds = $this->stats->classesOf($user)->pluck('id');
        $activities = Activity::query()->with(['phase', 'mode', 'schoolClass'])->withCount(['questions', 'matches'])
            ->when($user->role !== User::ADMIN, fn ($q) => $q->where(fn ($w) => $w->whereNull('school_class_id')->orWhereIn('school_class_id', $classIds)))
            ->orderByDesc('is_active')->orderBy('phase_id')->orderBy('title')->get();

        $stats = GameMatch::query()->where('status', GameMatch::COMPLETED)->whereNotNull('activity_id')->where('answered_questions', '>', 0)
            ->groupBy('activity_id')->selectRaw('activity_id, AVG(correct_count * 100.0 / answered_questions) as acc')->pluck('acc', 'activity_id');

        return view('teacher.activities.index', ['activities' => $activities, 'accuracy' => $stats, 'classroom' => GameMode::byCode(GameMode::CLASSROOM)]);
    }

    public function create(Request $request)
    {
        return view('teacher.activities.form', $this->formData($request, new Activity([
            'mode_id' => GameMode::byCode(GameMode::QUIZ)?->id, 'estimated_minutes' => 5, 'reward_points' => 100, 'icon' => 'map', 'is_active' => true,
        ]), []));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $activity = Activity::query()->create(collect($data)->except('question_ids')->all() + ['created_by' => $request->user()->id]);
        $activity->questions()->sync($data['question_ids']);

        return redirect()->route('teacher.activities.index')->with('status', 'Atividade criada!');
    }

    public function edit(Request $request, Activity $activity)
    {
        return view('teacher.activities.form', $this->formData($request, $activity, $activity->questions()->pluck('questions.id')->all()));
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $this->validated($request);
        $activity->update(collect($data)->except('question_ids')->all());
        $activity->questions()->sync($data['question_ids']);

        return redirect()->route('teacher.activities.index')->with('status', 'Atividade atualizada!');
    }

    public function destroy(Activity $activity)
    {
        if ($activity->matches()->exists()) {
            $activity->update(['is_active' => false]);

            return back()->with('status', 'A atividade já foi jogada, então foi desativada (o histórico foi mantido).');
        }
        $activity->delete();

        return back()->with('status', 'Atividade removida.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'phase_id' => 'required|integer|exists:phases,id',
            'mode_id' => 'required|integer|exists:game_modes,id',
            'focus_region_id' => 'nullable|integer|exists:map_regions,id',
            'school_class_id' => 'nullable|integer|exists:school_classes,id',
            'estimated_minutes' => 'required|integer|min:1|max:120',
            'reward_points' => 'required|integer|min:0|max:10000',
            'icon' => ['required', Rule::in(Activity::ICONS)],
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:questions,id',
        ], ['question_ids.required' => 'Selecione ao menos uma pergunta.'], ['title' => 'título', 'phase_id' => 'fase', 'mode_id' => 'modo']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function formData(Request $request, Activity $activity, array $selected): array
    {
        return [
            'activity' => $activity,
            'selected' => $selected,
            'phases' => Phase::query()->orderBy('number')->get(),
            'modes' => GameMode::query()->where('code', '!=', GameMode::EXPLORATION)->orderBy('id')->get(),
            'regions' => MapRegion::query()->where('type', 'REGION')->orderBy('name')->get(),
            'classes' => $this->stats->classesOf($request->user()),
            'questions' => Question::query()->where('is_active', true)->with(['answerRegion', 'phase', 'category'])->orderBy('phase_id')->orderBy('prompt')->get(),
        ];
    }
}
