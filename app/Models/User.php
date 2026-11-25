<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Jogador (tabela players). Alunos, professores e administradores. RN01
 */
class User extends Authenticatable
{
    use Notifiable;

    public const STUDENT = 'STUDENT';
    public const TEACHER = 'TEACHER';
    public const ADMIN = 'ADMIN';
    public const ROLES = [self::STUDENT, self::TEACHER, self::ADMIN];

    /** Avatares ilustrados (public/images/avatars/*.png) + avatares de cor com iniciais. */
    public const IMAGE_AVATARS = ['menina', 'menino', 'professora'];

    public const AVATARS = ['menina', 'menino', 'professora', 'sol', 'lua', 'estrela', 'onda', 'folha', 'raio', 'nuvem', 'flor'];

    protected $table = 'players';

    /** A senha fica em password_hash, como na API original. */
    protected $authPasswordName = 'password_hash';

    protected $fillable = ['name', 'nickname', 'email', 'password_hash', 'role', 'avatar', 'school_class_id', 'last_login_at'];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::TEACHER, self::ADMIN], true);
    }

    public function isStudent(): bool
    {
        return $this->role === self::STUDENT;
    }

    public function displayName(): string
    {
        return $this->nickname ?: explode(' ', trim($this->name))[0];
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [''];
        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::TEACHER => 'Professor(a)',
            self::ADMIN => 'Administrador(a)',
            default => 'Aluno(a)',
        };
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function teachingClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'player_id');
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(PlayerPreference::class, 'player_id');
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements', 'player_id', 'achievement_id')
            ->withPivot('unlocked_at');
    }

    public function exploredRegions(): BelongsToMany
    {
        return $this->belongsToMany(MapRegion::class, 'explored_regions', 'player_id', 'region_id')
            ->withPivot('first_explored_at');
    }

    /** Preferências com valores padrão quando ainda não existem. */
    public function prefs(): PlayerPreference
    {
        return $this->preferences ?? new PlayerPreference(PlayerPreference::DEFAULTS + ['player_id' => $this->id]);
    }

    public static function avatarUrl(?string $avatar): ?string
    {
        return in_array($avatar, self::IMAGE_AVATARS, true) ? asset("images/avatars/{$avatar}.png") : null;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'avatarUrl' => self::avatarUrl($this->avatar),
            'schoolClassId' => $this->school_class_id,
            'createdAt' => optional($this->created_at)->toIso8601String(),
            'updatedAt' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
