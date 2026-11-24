<?php

namespace App\Domain;

use App\Models\GameMode;

/**
 * Pontuação (RN08): fácil 10, média 20, difícil 30.
 * - Acerto depois de errar: metade dos pontos.
 * - Bônus de velocidade (modo com tempo): até +50% proporcional ao tempo restante.
 * - Bônus de sequência: +5 por acerto seguido a partir do 3º, até +20.
 * - Treino/Exploração: sem pontuação competitiva.
 */
final class ScoringPolicy
{
    public const BASE_POINTS = ['EASY' => 10, 'MEDIUM' => 20, 'HARD' => 30];
    public const RETRY_MULTIPLIER = 0.5;
    public const MAX_SPEED_BONUS_RATIO = 0.5;
    public const STREAK_BONUS_FROM = 3;
    public const STREAK_BONUS_STEP = 5;
    public const MAX_STREAK_BONUS = 20;

    public const ZERO = ['base' => 0, 'speedBonus' => 0, 'streakBonus' => 0, 'total' => 0];

    /** @return array{base:int,speedBonus:int,streakBonus:int,total:int} */
    public function calculate(string $difficulty, GameMode $mode, int $attemptNumber, ?int $responseTimeMs, int $streakAfterAnswer): array
    {
        if (! $mode->is_scored) {
            return self::ZERO;
        }

        $full = self::BASE_POINTS[$difficulty] ?? 10;
        $base = $attemptNumber > 1 ? (int) round($full * self::RETRY_MULTIPLIER) : $full;
        $speed = $this->speedBonus($full, $mode, $attemptNumber, $responseTimeMs);
        $streak = $this->streakBonus($mode, $streakAfterAnswer);

        return ['base' => $base, 'speedBonus' => $speed, 'streakBonus' => $streak, 'total' => $base + $speed + $streak];
    }

    private function speedBonus(int $full, GameMode $mode, int $attempt, ?int $ms): int
    {
        if (! $mode->speed_bonus || ! $mode->is_timed || ! $mode->time_limit_seconds) {
            return 0;
        }
        if ($attempt > 1 || $ms === null) {
            return 0;
        }
        $remaining = max(0, 1 - $ms / ($mode->time_limit_seconds * 1000));

        return (int) round($full * self::MAX_SPEED_BONUS_RATIO * $remaining);
    }

    private function streakBonus(GameMode $mode, int $streak): int
    {
        if (! $mode->streak_bonus || $streak < self::STREAK_BONUS_FROM) {
            return 0;
        }

        return min(($streak - self::STREAK_BONUS_FROM + 1) * self::STREAK_BONUS_STEP, self::MAX_STREAK_BONUS);
    }
}
