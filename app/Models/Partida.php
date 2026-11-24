<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partida extends Model
{
    use HasFactory;

    protected $table = 'Partida';
    protected $primaryKey = 'id_partida';
    public $timestamps = false;
    
    protected $fillable = ['id_jogador', 'id_modo', 'data_inicio', 'pontuacao_total', 'status_partida'];

    public function jogador()
    {
        return $this->belongsTo(Jogador::class, 'id_jogador', 'id_jogador');
    }

    public function modo()
    {
        return $this->belongsTo(ModoJogo::class, 'id_modo', 'id_modo');
    }
}