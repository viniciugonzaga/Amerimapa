<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Atividade (missão) criada pelo professor. */
class Activity extends Model
{
    public const ICONS = ['map', 'globe', 'compass', 'flag', 'pin', 'star'];

    protected $fillable = ['title', 'description', 'phase_id', 'mode_id', 'focus_region_id', 'school_class_id', 'created_by', 'estimated_minutes', 'reward_points', 'icon', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class, 'mode_id');
    }

    public function focusRegion(): BelongsTo
    {
        return $this->belongsTo(MapRegion::class, 'focus_region_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'activity_question');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    /** Atividades que um aluno (ou visitante, $user = null) pode ver. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $query->where('is_active', true);
        if ($user === null) {
            return $query->whereNull('school_class_id');
        }
        if ($user->isStaff()) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->whereNull('school_class_id')
            ->orWhere('school_class_id', $user->school_class_id));
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'phaseId' => $this->phase_id,
            'modeId' => $this->mode_id,
            'focusRegionId' => $this->focus_region_id,
            'schoolClassId' => $this->school_class_id,
            'estimatedMinutes' => $this->estimated_minutes,
            'rewardPoints' => $this->reward_points,
            'icon' => $this->icon,
            'isActive' => $this->is_active,
            'questionsCount' => $this->questions_count ?? $this->questions()->count(),
        ];
    }
}
