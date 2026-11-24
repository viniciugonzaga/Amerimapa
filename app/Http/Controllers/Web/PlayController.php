<?php

namespace App\Http\Controllers\Web;

use App\Domain\Actor;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\GameMatch;
use App\Models\GameMode;
use App\Models\MapRegion;
use App\Models\Phase;
use App\Services\AchievementService;
use App\Services\ProgressService;
use App\Services\StatsService;
use Illuminate\Http\Request;

/** Telas de jogo: lista de atividades, partida, exploração e resultado. */
class PlayController extends Controller
{
    public function __construct(
        private readonly StatsService $stats,
        private readonly ProgressService $progress,
    ) {}

    private function actor(Request $request): Actor
    {
        return $request->user() ? Actor::player($request->user()) : Actor::guest($request->session()->get('guest_id'));
    }

    public function activities(Request $request)
    {
        $actor = $this->actor($request);

        return view('student.activities', [
            'activities' => $this->stats->activitiesFor($request->user(), $actor),
            'phases' => $this->progress->availablePhases($actor),
            'modes' => GameMode::query()->orderBy('id')->get(),
        ]);
    }

    /**
     * /jogar?atividade=3  ·  /jogar?modo=4&fase=2  ·  /jogar?partida=12 (retomar)
     * A partida é criada/conduzida pelo JavaScript via API (/api/v1/matches).
     */
    public function play(Request $request)
    {
        $activity = $request->integer('atividade') ? Activity::query()->visibleTo($request->user())->with(['mode', 'phase'])->find($request->integer('atividade')) : null;
        $match = $request->integer('partida') ? GameMatch::query()->with(['mode', 'phase', 'activity'])->find($request->integer('partida')) : null;
        if ($match && ! $this->actor($request)->canAccessMatch($match)) {
            abort(403);
        }
        if ($match && $match->status !== GameMatch::IN_PROGRESS) {
            return redirect()->route('match.result', $match->id);
        }

        $mode = $request->integer('modo') ? GameMode::query()->find($request->integer('modo')) : null;
        $phase = $request->integer('fase') ? Phase::query()->find($request->integer('fase')) : null;
        if (! $match && ! $activity && (! $mode || ! $phase)) {
            return redirect()->route('activities')->with('status', 'Escolha uma atividade ou um modo e uma fase para jogar.');
        }
        if ($mode?->code === GameMode::EXPLORATION) {
            return redirect()->route('explore');
        }

        $focusId = $match?->activity?->focus_region_id ?? $activity?->focus_region_id;
        $focusIsos = $focusId ? MapRegion::query()->whereIn('id', MapRegion::query()->find($focusId)?->subtreeIds() ?? [])->whereNotNull('iso_code')->pluck('iso_code')->all() : [];

        return view('student.play', [
            'activity' => $activity ?? $match?->activity,
            'match' => $match,
            'mode' => $match?->mode ?? $mode ?? $activity?->mode,
            'phase' => $match?->phase ?? $phase ?? $activity?->phase,
            'focusIsos' => $focusIsos,
            'defaultDevice' => config('amerimapa.default_device_id'),
        ]);
    }

    public function explore(Request $request, AchievementService $achievements)
    {
        $explored = $request->user()
            ? MapRegion::query()->whereIn('id', $request->user()->exploredRegions()->pluck('map_regions.id'))->whereNotNull('iso_code')->pluck('iso_code')->all()
            : [];

        return view('student.explore', [
            'explored' => $explored,
            'countriesTotal' => MapRegion::query()->where('type', 'COUNTRY')->count(),
            'defaultDevice' => config('amerimapa.default_device_id'),
        ]);
    }

    public function result(Request $request, GameMatch $match, AchievementService $achievements)
    {
        if (! $this->actor($request)->canAccessMatch($match)) {
            abort(403);
        }
        $match->load(['mode', 'phase', 'activity', 'answers' => fn ($q) => $q->orderBy('id'), 'answers.question.answerRegion', 'answers.touchedRegion']);
        $finalAnswers = $match->answers->groupBy('question_id')->map(fn ($g) => $g->last());

        $recent = $request->user()
            ? $request->user()->achievements()->wherePivot('unlocked_at', '>=', $match->started_at)->get()
            : collect();

        return view('student.result', [
            'match' => $match,
            'answers' => $finalAnswers->values(),
            'newAchievements' => $recent,
            'training' => GameMode::byCode(GameMode::TRAINING),
        ]);
    }
}
