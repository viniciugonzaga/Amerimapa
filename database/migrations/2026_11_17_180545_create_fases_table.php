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
    Schema::create('Fase', function (Blueprint $table) {
        $table->id('id_fase');
        $table->integer('numero_fase');
        $table->string('titulo', 100);
        $table->integer('pontos_minimos_desbloqueio');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fases');
    }
};
