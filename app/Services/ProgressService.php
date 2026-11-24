<?php

namespace App\Services;

use App\Domain\Actor;
use App\Domain\ProgressionPolicy;
use App\Exceptions\ApiException;
use App\Models\GameMatch;
use App\Models\Phase;
use App\Models\User;

/**
 * Progressão do jogador (RN11, RN14).
 * Alunos cadastrados desbloqueiam fases em ordem; visitantes não têm progresso
 * salvo (RN01), então todas as fases ficam abertas; professores abrem qualquer fase.
 */
class ProgressService
{
    public function __construct(private readonly ProgressionPolicy $policy) {}

    /** @return array<int, array{bestScore:int, completedMatches:int}> */
    public function phaseResults(int $playerId): array
    {
        return GameMatch::query()
            ->where('player_id', $playerId)
            ->where('status', GameMatch::COMPLETED)
            ->groupBy('phase_id')
            ->selectRaw('phase_id, MAX(total_score) as best, COUNT(*) as cnt')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->phase_id => ['bestScore' => (int) $r->best, 'completedMatches' => (int) $r->cnt]])
            ->all();
    }

    public function playerProgress(User $player): array
    {
        $progress = $this->policy->evaluate(Phase::query()->get(), $this->phaseResults($player->id));

        return [
            'playerId' => $player->id,
            'totalBestScore' => array_sum(array_column($progress, 'bestScore')),
            'completedPhases' => count(array_filter($progress, fn ($p) => $p['completedMatches'] > 0)),
            'phases' => $progress,
        ];
    }

    public function availablePhases(?Actor $actor): array
    {
        if ($actor?->isPlayer() && ! $actor->isStaff()) {
            return $this->playerProgress($actor->player)['phases'];
        }

        return array_map(fn ($p) => ['unlocked' => true] + $p, $this->policy->evaluate(Phase::query()->get(), []));
    }

    public function isUnlocked(?Actor $actor, int $phaseId): bool
    {
        foreach ($this->availablePhases($actor) as $p) {
            if ($p['phase']->id === $phaseId) {
                return $p['unlocked'];
            }
        }

        return false;
    }

    public function assertPhaseUnlocked(Actor $actor, int $phaseId): void
    {
        if (! $actor->isPlayer() || $actor->isStaff()) {
            return;
        }
        foreach ($this->availablePhases($actor) as $p) {
            if ($p['phase']->id === $phaseId && ! $p['unlocked']) {
                $r = $p['requirement'];
                throw ApiException::rule('PHASE_LOCKED', "A fase {$p['phase']->number} está bloqueada. Conclua a fase {$r['previousPhaseNumber']} com pelo menos {$r['minScore']} pontos.");
            }
        }
    }

    public static function phaseProgressToApi(array $p): array
    {
        return [
            'phase' => $p['phase']->toApi(),
            'unlocked' => $p['unlocked'],
            'bestScore' => $p['bestScore'],
            'completedMatches' => $p['completedMatches'],
            'requirement' => $p['requirement'],
        ];
    }
}
