<?php

namespace App\Services;

use App\Domain\Actor;
use App\Models\Activity;
use App\Models\Device;
use App\Models\GameMatch;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Números das telas: painel do aluno (Início/Progresso) e do professor (Visão geral).
 */
class StatsService
{
    public const WEEKDAYS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    public function __construct(
        private readonly ProgressService $progress,
        private readonly AchievementService $achievements,
    ) {}

    // ─── Aluno ──────────────────────────────────────────────────────────────

    /** Atividades visíveis ao jogador com melhor resultado e se estão liberadas. */
    public function activitiesFor(?User $user, ?Actor $actor = null): Collection
    {
        $actor ??= $user ? Actor::player($user) : null;
        $unlocked = collect($this->progress->availablePhases($actor))
            ->mapWithKeys(fn ($p) => [$p['phase']->id => $p['unlocked']]);

        $best = collect();
        if ($user) {
            $best = GameMatch::query()->where('player_id', $user->id)->where('status', GameMatch::COMPLETED)
                ->whereNotNull('activity_id')->where('answered_questions', '>', 0)
                ->groupBy('activity_id')
                ->selectRaw('activity_id, MAX(correct_count * 100 / answered_questions) as best, MAX(total_score) as score, COUNT(*) as plays')
                ->get()->keyBy('activity_id');
        }

        return Activity::query()->visibleTo($user)->with(['phase', 'mode', 'focusRegion', 'schoolClass'])
            ->withCount('questions')->orderBy('phase_id')->orderBy('id')->get()
            ->map(function (Activity $a) use ($unlocked, $best) {
                $row = $best->get($a->id);
                $a->setAttribute('best_percent', $row ? (int) round($row->best) : null);
                $a->setAttribute('best_score', $row ? (int) $row->score : null);
                $a->setAttribute('plays', $row ? (int) $row->plays : 0);
                $a->setAttribute('completed', $row !== null);
                $a->setAttribute('unlocked', (bool) ($unlocked[$a->phase_id] ?? false));

                return $a;
            });
    }

    public function studentSummary(User $user): array
    {
        $totals = GameMatch::query()->where('player_id', $user->id)
            ->selectRaw('COALESCE(SUM(total_score),0) as points, COALESCE(SUM(correct_count),0) as correct, COALESCE(SUM(answered_questions),0) as answered')
            ->first();

        $activities = $this->activitiesFor($user);
        $done = $activities->where('completed', true)->count();
        $total = $activities->count();
        $achievements = $this->achievements->forPlayer($user);

        $next = $activities->first(fn ($a) => $a->unlocked && ! $a->completed)
            ?? $activities->first(fn ($a) => $a->unlocked);
        $review = $activities->filter(fn ($a) => $a->completed && $a->best_percent < 100)->sortBy('best_percent')->first()
            ?? $activities->filter(fn ($a) => $a->completed)->sortBy('best_percent')->first();

        return [
            'points' => (int) $totals->points,
            'accuracy' => $totals->answered > 0 ? (int) round($totals->correct * 100 / $totals->answered) : 0,
            'activitiesDone' => $done,
            'activitiesTotal' => $total,
            'overallPercent' => $total > 0 ? (int) round($done * 100 / $total) : 0,
            'achievementsUnlocked' => count(array_filter($achievements, fn ($a) => $a['status'] === 'UNLOCKED')),
            'achievements' => $achievements,
            'activities' => $activities,
            'nextMission' => $next,
            'review' => $review,
            'evolution' => $this->dailyAccuracy(fn ($q) => $q->where('player_id', $user->id)),
            'recentMatches' => GameMatch::query()->where('player_id', $user->id)->with(['mode', 'activity', 'phase'])
                ->orderByDesc('id')->limit(5)->get(),
        ];
    }

    /**
     * Acertos (%) por dia nos últimos 7 dias.
     *
     * @return array{labels: string[], values: array<int, int|null>}
     */
    public function dailyAccuracy(callable $scope, int $days = 7): array
    {
        $from = Carbon::today()->subDays($days - 1);
        $rows = $scope(GameMatch::query())->where('started_at', '>=', $from)
            ->get(['started_at', 'correct_count', 'answered_questions'])
            ->groupBy(fn ($m) => $m->started_at->toDateString());

        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $labels[] = self::WEEKDAYS[$day->dayOfWeek];
            $group = $rows->get($day->toDateString());
            $answered = $group ? $group->sum('answered_questions') : 0;
            $values[] = $answered > 0 ? (int) round($group->sum('correct_count') * 100 / $answered) : null;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    // ─── Professor ──────────────────────────────────────────────────────────

    public function classesOf(User $teacher): Collection
    {
        $query = SchoolClass::query()->withCount('students')->orderBy('name');
        if ($teacher->role !== User::ADMIN) {
            $query->where('teacher_id', $teacher->id);
        }

        return $query->get();
    }

    public function studentIdsOf(User $teacher): array
    {
        return User::query()->where('role', User::STUDENT)
            ->whereIn('school_class_id', $this->classesOf($teacher)->pluck('id'))
            ->pluck('id')->all();
    }

    public function teacherOverview(User $teacher): array
    {
        $classes = $this->classesOf($teacher);
        $studentIds = $this->studentIdsOf($teacher);
        $weekAgo = now()->subDays(7);

        $activityQuery = Activity::query()->when($teacher->role !== User::ADMIN, fn ($q) => $q->where(
            fn ($w) => $w->where('created_by', $teacher->id)->orWhereIn('school_class_id', $classes->pluck('id'))
        ));

        $acc = fn ($q) => (clone $q)->selectRaw('COALESCE(SUM(correct_count),0) c, COALESCE(SUM(answered_questions),0) a')->first();
        $base = GameMatch::query()->whereIn('player_id', $studentIds);
        $all = $acc($base);
        $before = $acc((clone $base)->where('started_at', '<', $weekAgo));
        $avg = $all->a > 0 ? (int) round($all->c * 100 / $all->a) : 0;
        $avgBefore = $before->a > 0 ? (int) round($before->c * 100 / $before->a) : $avg;

        $series = $classes->map(fn (SchoolClass $c) => [
            'name' => $c->name,
            'color' => $c->color,
            'values' => $this->dailyAccuracy(fn ($q) => $q->whereIn('player_id', User::query()->where('school_class_id', $c->id)->pluck('id')))['values'],
        ])->values()->all();

        return [
            'classes' => $classes,
            'counts' => [
                'classes' => $classes->count(),
                'classesDelta' => $classes->where('created_at', '>=', $weekAgo)->count(),
                'students' => count($studentIds),
                'studentsDelta' => User::query()->whereIn('id', $studentIds)->where('created_at', '>=', $weekAgo)->count(),
                'activities' => (clone $activityQuery)->count(),
                'activitiesDelta' => (clone $activityQuery)->where('created_at', '>=', $weekAgo)->count(),
                'accuracy' => $avg,
                'accuracyDelta' => $avg - $avgBefore,
            ],
            'chart' => ['labels' => $this->dailyAccuracy(fn ($q) => $q->whereRaw('1 = 0'))['labels'], 'series' => $series],
            'recentActivities' => (clone $activityQuery)->with('schoolClass')->latest('id')->limit(3)->get(),
            'ranking' => $this->ranking($studentIds, 5),
            'device' => Device::query()->orderByDesc('last_seen_at')->first(),
        ];
    }

    /**
     * Ranking por pontos em partidas concluídas (RN21: só partidas concluídas contam).
     *
     * @param  int[]|null  $playerIds
     */
    public function ranking(?array $playerIds, int $limit = 10): Collection
    {
        return DB::table('matches')
            ->join('players', 'players.id', '=', 'matches.player_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'players.school_class_id')
            ->where('matches.status', GameMatch::COMPLETED)
            ->when($playerIds !== null, fn ($q) => $q->whereIn('matches.player_id', $playerIds))
            ->groupBy('players.id', 'players.name', 'players.avatar', 'school_classes.name')
            ->selectRaw('players.id, players.name, players.avatar, school_classes.name as class_name, SUM(matches.total_score) as points, COUNT(*) as matches, SUM(matches.correct_count) as correct, SUM(matches.answered_questions) as answered')
            ->orderByDesc('points')
            ->limit($limit)
            ->get()
            ->map(function ($r, $i) {
                $r->rank = $i + 1;
                $r->accuracy = $r->answered > 0 ? (int) round($r->correct * 100 / $r->answered) : 0;

                return $r;
            });
    }

    /** Relatório por aluno de uma turma (tela Relatórios e exportação CSV). */
    public function classReport(array $studentIds): Collection
    {
        $students = User::query()->whereIn('id', $studentIds)->with('schoolClass')->orderBy('name')->get();
        $agg = GameMatch::query()->whereIn('player_id', $studentIds)
            ->groupBy('player_id')
            ->selectRaw("player_id, COUNT(*) as total, SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed, SUM(total_score) as points, SUM(correct_count) as correct, SUM(answered_questions) as answered, MAX(best_streak) as streak, MAX(started_at) as last_played")
            ->get()->keyBy('player_id');

        return $students->map(function (User $s) use ($agg) {
            $r = $agg->get($s->id);

            return [
                'student' => $s,
                'matches' => (int) ($r->total ?? 0),
                'completed' => (int) ($r->completed ?? 0),
                'points' => (int) ($r->points ?? 0),
                'accuracy' => ($r && $r->answered > 0) ? (int) round($r->correct * 100 / $r->answered) : null,
                'bestStreak' => (int) ($r->streak ?? 0),
                'lastPlayed' => $r?->last_played ? Carbon::parse($r->last_played) : null,
            ];
        });
    }

    /** Perguntas/regiões em que a turma mais erra (análise pedagógica). */
    public function hardestQuestions(array $studentIds, int $limit = 5): Collection
    {
        return DB::table('match_answers')
            ->join('matches', 'matches.id', '=', 'match_answers.match_id')
            ->join('questions', 'questions.id', '=', 'match_answers.question_id')
            ->whereIn('matches.player_id', $studentIds)
            ->groupBy('questions.id', 'questions.prompt')
            ->selectRaw("questions.id, questions.prompt, COUNT(*) as attempts, SUM(CASE WHEN match_answers.result = 'CORRECT' THEN 1 ELSE 0 END) as correct")
            ->havingRaw('COUNT(*) >= 2')
            ->get()
            ->map(function ($r) {
                $r->accuracy = (int) round($r->correct * 100 / max(1, $r->attempts));

                return $r;
            })
            ->sortBy('accuracy')->take($limit)->values();
    }
}
