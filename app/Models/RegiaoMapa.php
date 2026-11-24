<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegiaoMapa extends Model
{
    use HasFactory;

    protected $table = 'Regiao_Mapa';
    protected $primaryKey = 'id_regiao';
    public $timestamps = false;
    
    protected $fillable = ['id_sensor', 'nome_regiao', 'categoria_tipo'];

    public function sensor()
    {
        return $this->belongsTo(HardwareSensor::class, 'id_sensor', 'id_sensor');
    }
}