<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Conquista (emblema). */
class Achievement extends Model
{
    public const RULES = [
        'MATCHES_COMPLETED' => 'Partidas concluídas',
        'TOTAL_POINTS' => 'Pontos acumulados',
        'BEST_STREAK' => 'Acertos seguidos',
        'COUNTRIES_EXPLORED' => 'Países explorados',
        'REGIONS_EXPLORED' => 'Regiões conhecidas',
        'PHASES_COMPLETED' => 'Fases concluídas',
    ];

    protected $fillable = ['code', 'name', 'description', 'icon', 'color', 'rule', 'target', 'sort_order'];
}
