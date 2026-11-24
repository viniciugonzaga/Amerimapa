<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Turma. */
class SchoolClass extends Model
{
    public const COLORS = ['blue', 'green', 'orange', 'pink', 'purple', 'teal'];

    protected $fillable = ['name', 'grade', 'color', 'teacher_id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'school_class_id')->where('role', User::STUDENT);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'grade' => $this->grade,
            'color' => $this->color,
            'teacherId' => $this->teacher_id,
            'studentsCount' => $this->students_count ?? $this->students()->count(),
        ];
    }
}
