<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Modo de jogo. RN08, RN09, RN12 */
class GameMode extends Model
{
    public const EXPLORATION = 'EXPLORATION';
    public const TRAINING = 'TRAINING';
    public const QUIZ = 'QUIZ';
    public const CHALLENGE = 'CHALLENGE';
    public const CLASSROOM = 'CLASSROOM';

    protected $fillable = ['code', 'name', 'description', 'is_timed', 'time_limit_seconds', 'max_attempts', 'questions_per_match', 'is_scored', 'speed_bonus', 'streak_bonus'];

    protected function casts(): array
    {
        return [
            'is_timed' => 'boolean',
            'is_scored' => 'boolean',
            'speed_bonus' => 'boolean',
            'streak_bonus' => 'boolean',
            'time_limit_seconds' => 'integer',
            'max_attempts' => 'integer',
            'questions_per_match' => 'integer',
        ];
    }

    public static function byCode(string $code): ?self
    {
        return self::query()->where('code', $code)->first();
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'isTimed' => $this->is_timed,
            'timeLimitSeconds' => $this->time_limit_seconds,
            'maxAttempts' => $this->max_attempts,
            'questionsPerMatch' => $this->questions_per_match,
            'isScored' => $this->is_scored,
            'speedBonus' => $this->speed_bonus,
            'streakBonus' => $this->streak_bonus,
        ];
    }
}
