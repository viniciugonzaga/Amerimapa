<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AchievementService;
use App\Services\ProgressService;
use App\Services\StatsService;
use Illuminate\Http\Request;

/** Início, Progresso e Conquistas do aluno. */
class StudentController extends Controller
{
    public function __construct(
        private readonly StatsService $stats,
        private readonly AchievementService $achievements,
        private readonly ProgressService $progress,
    ) {}

    public function home(Request $request)
    {
        return view('student.home', ['s' => $this->stats->studentSummary($request->user())]);
    }

    public function progress(Request $request)
    {
        $user = $request->user();

        return view('student.progress', [
            's' => $this->stats->studentSummary($user, StatsService::period($request->query('periodo'))),
            'days' => StatsService::period($request->query('periodo')),
            'phases' => $this->progress->playerProgress($user)['phases'],
        ]);
    }

    public function achievements(Request $request)
    {
        $user = $request->user();
        $list = $this->achievements->forPlayer($user);
        $next = collect($list)->where('status', '!=', 'UNLOCKED')->sortByDesc('percent')->first();
        $summary = $this->stats->studentSummary($user);

        return view('student.achievements', [
            'list' => $list,
            'unlocked' => collect($list)->where('status', 'UNLOCKED')->count(),
            'points' => $summary['points'],
            'next' => $next,
            'ranking' => $user->school_class_id
                ? $this->stats->ranking(\App\Models\User::query()->where('school_class_id', $user->school_class_id)->pluck('id')->all(), 10)
                : collect(),
        ]);
    }
}
