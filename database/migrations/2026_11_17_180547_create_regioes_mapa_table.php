<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('Regiao_Mapa', function (Blueprint $table) {
        $table->id('id_regiao');
        $table->unsignedBigInteger('id_sensor');
        $table->string('nome_regiao', 100);
        $table->string('categoria_tipo', 50);
        
        $table->foreign('id_sensor')->references('id_sensor')->on('Hardware_Sensor')->onDelete('cascade')->onUpdate('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regioes_mapa');
    }
};
