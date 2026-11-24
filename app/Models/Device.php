<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mapa físico (ESP32). */
class Device extends Model
{
    protected $primaryKey = 'device_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['device_id', 'name', 'firmware_version', 'sensors_ok', 'battery_level', 'last_seen_at', 'bound_match_id', 'announced_online'];

    protected function casts(): array
    {
        return [
            'sensors_ok' => 'boolean',
            'announced_online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->diffInSeconds(now(), true) <= config('amerimapa.hardware_timeout_seconds');
    }

    public function secondsSinceLastSeen(): ?int
    {
        return $this->last_seen_at ? (int) round($this->last_seen_at->diffInSeconds(now(), true)) : null;
    }

    public function toApi(): array
    {
        return [
            'deviceId' => $this->device_id,
            'name' => $this->name,
            'firmwareVersion' => $this->firmware_version,
            'sensorsOk' => $this->sensors_ok,
            'batteryLevel' => $this->battery_level,
            'lastSeenAt' => optional($this->last_seen_at)->toIso8601ZuluString('millisecond'),
            'boundMatchId' => $this->bound_match_id,
            'online' => $this->isOnline(),
            'secondsSinceLastSeen' => $this->secondsSinceLastSeen(),
        ];
    }
}
