<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Evento em tempo real (toque, resposta, mapa conectado/desconectado). */
class GameEvent extends Model
{
    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['type', 'device_id', 'match_id', 'payload', 'created_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'created_at' => 'datetime'];
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'deviceId' => $this->device_id,
            'matchId' => $this->match_id,
            'payload' => $this->payload,
            'createdAt' => optional($this->created_at)->toIso8601ZuluString('millisecond'),
        ];
    }
}
