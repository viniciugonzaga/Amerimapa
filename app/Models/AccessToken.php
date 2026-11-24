<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Token Bearer da API (somente o hash é salvo). */
class AccessToken extends Model
{
    public $timestamps = false;

    protected $fillable = ['token_hash', 'player_id', 'guest_id', 'last_used_at', 'expires_at', 'created_at'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'expires_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
