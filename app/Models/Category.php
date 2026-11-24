<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'description'];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function toApi(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description];
    }
}
