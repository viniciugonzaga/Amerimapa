<?php

namespace App\Http\Controllers\Api;

use App\Models\GameMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RN21: só partidas CONCLUÍDAS de jogadores cadastrados entram no ranking.
 * Uma linha por jogador: a melhor pontuação dele.
 */
class LeaderboardController extends ApiController
{
    public function __invoke(Request $request)
    {
        $f = $request->validate([
            'phaseId' => 'sometimes|integer', 'modeId' => 'sometimes|integer',
            'schoolClassId' => 'sometimes|integer', 'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $rows = DB::table('matches')
            ->join('players', 'players.id', '=', 'matches.player_id')
            ->where('matches.status', GameMatch::COMPLETED)
            ->when(isset($f['phaseId']), fn ($q) => $q->where('matches.phase_id', $f['phaseId']))
            ->when(isset($f['modeId']), fn ($q) => $q->where('matches.mode_id', $f['modeId']))
            ->when(isset($f['schoolClassId']), fn ($q) => $q->where('players.school_class_id', $f['schoolClassId']))
            ->groupBy('players.id', 'players.name', 'players.avatar')
            ->selectRaw('players.id as playerId, players.name as playerName, players.avatar, MAX(matches.total_score) as bestScore, COUNT(*) as completedMatches')
            ->orderByDesc('bestScore')
            ->limit($f['limit'] ?? 10)
            ->get();

        return $this->ok($rows->values()->map(fn ($r, $i) => [
            'rank' => $i + 1,
            'playerId' => (int) $r->playerId,
            'playerName' => $r->playerName,
            'avatar' => $r->avatar,
            'bestScore' => (int) $r->bestScore,
            'completedMatches' => (int) $r->completedMatches,
        ]));
    }
}
