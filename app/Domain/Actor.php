<?php

namespace App\Domain;

use App\Models\GameMatch;
use App\Models\User;

/**
 * Quem está fazendo a requisição: jogador cadastrado, visitante ou mapa (ESP32).
 */
final class Actor
{
    public const PLAYER = 'PLAYER';
    public const GUEST = 'GUEST';
    public const DEVICE = 'DEVICE';

    private function __construct(
        public readonly string $type,
        public readonly ?User $player = null,
        public readonly ?string $guestId = null,
        public readonly ?string $deviceId = null,
    ) {}

    public static function player(User $player): self
    {
        return new self(self::PLAYER, player: $player);
    }

    public static function guest(string $guestId): self
    {
        return new self(self::GUEST, guestId: $guestId);
    }

    public static function device(string $deviceId): self
    {
        return new self(self::DEVICE, deviceId: $deviceId);
    }

    public function playerId(): ?int
    {
        return $this->player?->id;
    }

    public function isPlayer(): bool
    {
        return $this->type === self::PLAYER;
    }

    public function isGuest(): bool
    {
        return $this->type === self::GUEST;
    }

    public function isStaff(): bool
    {
        return $this->isPlayer() && $this->player->isStaff();
    }

    public function isAdmin(): bool
    {
        return $this->isPlayer() && $this->player->role === User::ADMIN;
    }

    /** Dono da partida, professor/admin, ou o mapa vinculado. */
    public function canAccessMatch(GameMatch $match): bool
    {
        return match ($this->type) {
            self::DEVICE => true, // o HardwareService confere o vínculo mapa-partida
            self::GUEST => $match->guest_id !== null && $match->guest_id === $this->guestId,
            self::PLAYER => $match->player_id === $this->playerId() || $this->isStaff(),
        };
    }
}
