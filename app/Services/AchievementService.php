<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\GameMatch;
use App\Models\MapRegion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Conquistas (emblemas): calcula o progresso de cada regra e grava os desbloqueios.
 */
class AchievementService
{
    /** Valores atuais de cada regra para o jogador. */
    public function metrics(User $player): array
    {
        $completed = GameMatch::query()->where('player_id', $player->id)->where('status', GameMatch::COMPLETED);

        $explored = DB::table('explored_regions')
            ->join('map_regions', 'map_regions.id', '=', 'explored_regions.region_id')
            ->where('explored_regions.player_id', $player->id)
            ->get(['map_regions.id', 'map_regions.type', 'map_regions.parent_id']);

        $regions = $explored->map(fn ($r) => $r->type === 'REGION' ? $r->id : $r->parent_id)->filter()->unique();

        return [
            'MATCHES_COMPLETED' => (clone $completed)->count(),
            'TOTAL_POINTS' => (int) GameMatch::query()->where('player_id', $player->id)->sum('total_score'),
            'BEST_STREAK' => (int) GameMatch::query()->where('player_id', $player->id)->max('best_streak'),
            'COUNTRIES_EXPLORED' => $explored->where('type', 'COUNTRY')->count(),
            'REGIONS_EXPLORED' => $regions->count(),
            'PHASES_COMPLETED' => (clone $completed)->distinct()->count('phase_id'),
        ];
    }

    /** Lista de conquistas com progresso, para telas e API. */
    public function forPlayer(User $player): array
    {
        $metrics = $this->metrics($player);
        $unlocked = DB::table('player_achievements')->where('player_id', $player->id)->pluck('unlocked_at', 'achievement_id');

        return Achievement::query()->orderBy('sort_order')->get()->map(function (Achievement $a) use ($metrics, $unlocked) {
            $current = min($metrics[$a->rule] ?? 0, $a->target);
            $isUnlocked = isset($unlocked[$a->id]);

            return [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'description' => $a->description,
                'icon' => $a->icon,
                'color' => $a->color,
                'rule' => $a->rule,
                'target' => $a->target,
                'current' => $isUnlocked ? $a->target : $current,
                'percent' => $isUnlocked ? 100 : (int) floor($current * 100 / max(1, $a->target)),
                'status' => $isUnlocked ? 'UNLOCKED' : ($current > 0 ? 'IN_PROGRESS' : 'LOCKED'),
                'unlockedAt' => $isUnlocked ? (string) $unlocked[$a->id] : null,
            ];
        })->all();
    }

    /** Grava conquistas recém-alcançadas. Retorna as novas (para comemorar na tela). */
    public function sync(User $player): array
    {
        $new = [];
        foreach ($this->forPlayer($player) as $a) {
            if ($a['status'] !== 'UNLOCKED' && $a['current'] >= $a['target']) {
                DB::table('player_achievements')->insertOrIgnore([
                    'player_id' => $player->id, 'achievement_id' => $a['id'], 'unlocked_at' => now(),
                ]);
                $new[] = ['code' => $a['code'], 'name' => $a['name'], 'icon' => $a['icon'], 'color' => $a['color']];
            }
        }

        return $new;
    }

    /** Registra que o jogador conheceu uma região (modo Exploração ou acerto). */
    public function markExplored(User $player, MapRegion $region): array
    {
        $ids = array_merge([$region->id], $region->type === 'COUNTRY' && $region->parent_id ? [$region->parent_id] : []);
        foreach ($ids as $id) {
            DB::table('explored_regions')->insertOrIgnore([
                'player_id' => $player->id, 'region_id' => $id, 'first_explored_at' => now(),
            ]);
        }

        return $this->sync($player);
    }
}
