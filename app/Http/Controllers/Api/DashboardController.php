<?php

namespace App\Http\Controllers\Api;

use App\Models\Achievement;
use App\Services\StatsService;
use Illuminate\Http\Request;

/** Dados agregados para o painel do professor e catálogo de conquistas. */
class DashboardController extends ApiController
{
    public function teacher(Request $request, StatsService $stats)
    {
        $o = $stats->teacherOverview($this->actor($request)->player);

        return $this->ok([
            'counts' => $o['counts'],
            'chart' => $o['chart'],
            'classes' => $o['classes']->map->toApi(),
            'recentActivities' => $o['recentActivities']->map->toApi(),
            'ranking' => $o['ranking']->map(fn ($r) => [
                'rank' => $r->rank, 'playerId' => $r->id, 'playerName' => $r->name, 'className' => $r->class_name,
                'points' => (int) $r->points, 'accuracy' => $r->accuracy,
            ]),
            'device' => $o['device']?->toApi(),
        ]);
    }

    public function achievements()
    {
        return $this->ok(Achievement::query()->orderBy('sort_order')->get(['id', 'code', 'name', 'description', 'icon', 'color', 'rule', 'target']));
    }
}
