<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jogador extends Model
{
    use HasFactory;

    protected $table = 'Jogador';
    protected $primaryKey = 'id_jogador';
    public $timestamps = false;
    
    protected $fillable = ['tipo_perfil', 'nome', 'email'];

    // Relacionamento: Um jogador tem várias partidas
    public function partidas()
    {
        return $this->hasMany(Partida::class, 'id_jogador', 'id_jogador');
    }
}