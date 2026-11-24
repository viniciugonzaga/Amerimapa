<?php

namespace Tests\Unit;

use App\Domain\AnswerFeedback;
use App\Domain\ScoringPolicy;
use App\Models\GameMode;
use PHPUnit\Framework\TestCase;

class ScoringPolicyTest extends TestCase
{
    private function mode(array $attrs = []): GameMode
    {
        return (new GameMode)->forceFill($attrs + ['is_scored' => true, 'is_timed' => false, 'time_limit_seconds' => null, 'speed_bonus' => false, 'streak_bonus' => false]);
    }

    public function test_base_points_by_difficulty(): void
    {
        $p = new ScoringPolicy;
        $this->assertSame(10, $p->calculate('EASY', $this->mode(), 1, 1000, 1)['total']);
        $this->assertSame(20, $p->calculate('MEDIUM', $this->mode(), 1, 1000, 1)['total']);
        $this->assertSame(30, $p->calculate('HARD', $this->mode(), 1, 1000, 1)['total']);
    }

    public function test_retry_is_worth_half(): void
    {
        $this->assertSame(15, (new ScoringPolicy)->calculate('HARD', $this->mode(), 2, 1000, 1)['total']);
    }

    public function test_training_has_no_competitive_score(): void
    {
        $this->assertSame(0, (new ScoringPolicy)->calculate('HARD', $this->mode(['is_scored' => false]), 1, 1000, 5)['total']);
    }

    public function test_speed_and_streak_bonus(): void
    {
        $mode = $this->mode(['is_timed' => true, 'time_limit_seconds' => 20, 'speed_bonus' => true, 'streak_bonus' => true]);
        $r = (new ScoringPolicy)->calculate('MEDIUM', $mode, 1, 0, 3);
        $this->assertSame(10, $r['speedBonus']);
        $this->assertSame(5, $r['streakBonus']);
        $this->assertSame(20, (new ScoringPolicy)->calculate('EASY', $mode, 1, 20000, 99)['streakBonus']);
    }

    public function test_touching_a_country_answers_its_region(): void
    {
        $this->assertTrue(AnswerFeedback::isCorrect(4, 28, [4]));
        $this->assertFalse(AnswerFeedback::isCorrect(28, 4, []));
        $this->assertSame(42, AnswerFeedback::trackNumber('mp3/0042.mp3'));
    }
}
