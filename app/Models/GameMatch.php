<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Partida (tabela matches). "Match" é palavra reservada no PHP 8, por isso GameMatch.
 */
class GameMatch extends Model
{
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const COMPLETED = 'COMPLETED';
    public const ABANDONED = 'ABANDONED';

    protected $table = 'matches';

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = [
        'player_id', 'guest_id', 'mode_id', 'phase_id', 'activity_id', 'status', 'total_score', 'correct_count',
        'wrong_count', 'streak', 'best_streak', 'answered_questions', 'active_question_id', 'question_started_at',
        'attempts_on_question', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'question_started_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'mode_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function activeQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'active_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(MatchAnswer::class, 'match_id');
    }

    public function accuracy(): ?int
    {
        return $this->answered_questions > 0
            ? (int) round($this->correct_count * 100 / $this->answered_questions)
            : null;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'playerId' => $this->player_id,
            'guestId' => $this->guest_id,
            'modeId' => $this->mode_id,
            'phaseId' => $this->phase_id,
            'activityId' => $this->activity_id,
            'status' => $this->status,
            'totalScore' => $this->total_score,
            'correctCount' => $this->correct_count,
            'wrongCount' => $this->wrong_count,
            'streak' => $this->streak,
            'bestStreak' => $this->best_streak,
            'answeredQuestions' => $this->answered_questions,
            'activeQuestionId' => $this->active_question_id,
            'questionStartedAt' => optional($this->question_started_at)->toIso8601ZuluString('millisecond'),
            'attemptsOnQuestion' => $this->attempts_on_question,
            'startedAt' => optional($this->started_at)->toIso8601ZuluString('millisecond'),
            'endedAt' => optional($this->ended_at)->toIso8601ZuluString('millisecond'),
        ];
    }
}
