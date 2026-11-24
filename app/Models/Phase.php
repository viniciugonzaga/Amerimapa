<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Fase (nível). RN02, RN11 */
class Phase extends Model
{
    protected $fillable = ['number', 'title', 'description', 'min_unlock_score'];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'minUnlockScore' => $this->min_unlock_score,
        ];
    }
}
