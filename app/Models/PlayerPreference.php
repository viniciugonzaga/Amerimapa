<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Preferências de acessibilidade. RN18 */
class PlayerPreference extends Model
{
    public const DEFAULTS = [
        'large_font' => false,
        'high_contrast' => false,
        'audio_instructions' => true,
        'reduced_motion' => false,
    ];

    protected $primaryKey = 'player_id';

    public $incrementing = false;

    protected $fillable = ['player_id', 'large_font', 'high_contrast', 'audio_instructions', 'reduced_motion'];

    protected function casts(): array
    {
        return [
            'large_font' => 'boolean',
            'high_contrast' => 'boolean',
            'audio_instructions' => 'boolean',
            'reduced_motion' => 'boolean',
        ];
    }

    public function bodyClasses(): string
    {
        return trim(implode(' ', array_filter([
            $this->large_font ? 'a11y-large-font' : null,
            $this->high_contrast ? 'a11y-high-contrast' : null,
            $this->reduced_motion ? 'a11y-reduced-motion' : null,
        ])));
    }

    public function toApi(): array
    {
        return [
            'largeFont' => (bool) $this->large_font,
            'highContrast' => (bool) $this->high_contrast,
            'audioInstructions' => (bool) $this->audio_instructions,
            'reducedMotion' => (bool) $this->reduced_motion,
        ];
    }
}
