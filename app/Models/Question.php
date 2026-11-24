<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pergunta. RN15 */
class Question extends Model
{
    public const DIFFICULTIES = ['EASY', 'MEDIUM', 'HARD'];

    protected $fillable = ['prompt', 'difficulty', 'answer_region_id', 'phase_id', 'category_id', 'audio_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function answerRegion(): BelongsTo
    {
        return $this->belongsTo(MapRegion::class, 'answer_region_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(MatchAnswer::class);
    }

    public static function difficultyLabel(string $difficulty): string
    {
        return ['EASY' => 'Fácil', 'MEDIUM' => 'Média', 'HARD' => 'Difícil'][$difficulty] ?? $difficulty;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            'difficulty' => $this->difficulty,
            'answerRegionId' => $this->answer_region_id,
            'phaseId' => $this->phase_id,
            'categoryId' => $this->category_id,
            'audioPath' => $this->audio_path,
            'isActive' => (bool) $this->is_active,
            'createdAt' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
