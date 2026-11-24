<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Http\Request;

/** Desempenho/ranking e relatórios (com exportação CSV). */
class ReportController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    public function performance(Request $request)
    {
        [$classes, $class, $ids] = $this->scope($request);

        return view('teacher.performance', [
            'classes' => $classes,
            'class' => $class,
            'ranking' => $this->stats->ranking($ids, 20),
            'hardest' => $this->stats->hardestQuestions($ids),
            'chart' => $this->stats->dailyAccuracy(fn ($q) => $q->whereIn('player_id', $ids)),
        ]);
    }

    public function reports(Request $request)
    {
        [$classes, $class, $ids] = $this->scope($request);

        return view('teacher.reports', [
            'classes' => $classes,
            'class' => $class,
            'report' => $this->stats->classReport($ids),
        ]);
    }

    public function export(Request $request)
    {
        [, $class, $ids] = $this->scope($request);
        $rows = $this->stats->classReport($ids);
        $name = 'amerimapa-relatorio-'.($class ? str($class->name)->slug() : 'todas-as-turmas').'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel abrir acentos
            fputcsv($out, ['Aluno', 'E-mail', 'Turma', 'Partidas', 'Concluídas', 'Pontos', 'Acertos (%)', 'Melhor sequência', 'Última partida'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['student']->name, $r['student']->email, $r['student']->schoolClass?->name, $r['matches'], $r['completed'],
                    $r['points'], $r['accuracy'] ?? '', $r['bestStreak'], $r['lastPlayed']?->format('d/m/Y H:i') ?? '',
                ], ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:\Illuminate\Support\Collection,1:?SchoolClass,2:int[]} */
    private function scope(Request $request): array
    {
        $classes = $this->stats->classesOf($request->user());
        $class = $request->integer('turma') ? $classes->firstWhere('id', $request->integer('turma')) : null;
        $ids = $class
            ? User::query()->where('role', User::STUDENT)->where('school_class_id', $class->id)->pluck('id')->all()
            : $this->stats->studentIdsOf($request->user());

        return [$classes, $class, $ids];
    }
}
