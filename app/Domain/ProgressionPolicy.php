<?php

namespace App\Domain;

use App\Models\Phase;
use Illuminate\Support\Collection;

/**
 * RN11: fases avançadas exigem concluir a fase anterior com pelo menos
 * `min_unlock_score` pontos. A primeira fase está sempre liberada.
 */
final class ProgressionPolicy
{
    /**
     * @param  Collection<int, Phase>  $phases
     * @param  array<int, array{bestScore:int, completedMatches:int}>  $results  indexado por phase_id
     * @return array<int, array{phase:Phase, unlocked:bool, bestScore:int, completedMatches:int, requirement:?array}>
     */
    public function evaluate(Collection $phases, array $results): array
    {
        $ordered = $phases->sortBy('number')->values();
        $out = [];
        foreach ($ordered as $i => $phase) {
            $own = $results[$phase->id] ?? ['bestScore' => 0, 'completedMatches' => 0];
            $previous = $i > 0 ? $ordered[$i - 1] : null;
            if ($previous === null) {
                $out[] = ['phase' => $phase, 'unlocked' => true, 'bestScore' => $own['bestScore'], 'completedMatches' => $own['completedMatches'], 'requirement' => null];

                continue;
            }
            $prev = $results[$previous->id] ?? null;
            $unlocked = $prev !== null && $prev['completedMatches'] > 0 && $prev['bestScore'] >= $phase->min_unlock_score;
            $out[] = [
                'phase' => $phase,
                'unlocked' => $unlocked,
                'bestScore' => $own['bestScore'],
                'completedMatches' => $own['completedMatches'],
                'requirement' => ['previousPhaseNumber' => $previous->number, 'minScore' => $phase->min_unlock_score],
            ];
        }

        return $out;
    }
}
