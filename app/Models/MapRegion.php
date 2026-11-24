<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Região do mapa (região, país ou estado). RN05, RN19, RN22 */
class MapRegion extends Model
{
    public const TYPES = ['REGION', 'COUNTRY', 'STATE'];

    protected $fillable = ['name', 'hardware_pin', 'type', 'parent_id', 'iso_code', 'capital', 'language', 'short_description', 'info'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'answer_region_id');
    }

    /** IDs dos ancestrais (pai, avô...). Tocar no Brasil responde "Encontre a América do Sul". */
    public function ancestorIds(): array
    {
        $ids = [];
        $current = $this->parent_id;
        $guard = 0;
        while ($current !== null && $guard++ < 10) {
            $ids[] = $current;
            $current = self::query()->whereKey($current)->value('parent_id');
        }

        return $ids;
    }

    /** IDs desta região e de todas as descendentes. */
    public function subtreeIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];
        $guard = 0;
        while ($frontier && $guard++ < 10) {
            $frontier = self::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    public function typeLabel(): string
    {
        return ['REGION' => 'Região', 'COUNTRY' => 'País', 'STATE' => 'Estado'][$this->type] ?? $this->type;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hardwarePin' => $this->hardware_pin,
            'type' => $this->type,
            'parentId' => $this->parent_id,
            'isoCode' => $this->iso_code,
            'capital' => $this->capital,
            'language' => $this->language,
            'shortDescription' => $this->short_description,
            'info' => $this->info,
        ];
    }

    public function toRef(): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
