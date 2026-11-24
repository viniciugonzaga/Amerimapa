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
    Schema::create('Modo_Jogo', function (Blueprint $table) {
        $table->id('id_modo');
        $table->string('nome_modo', 50);
        $table->boolean('possui_tempo');
        $table->boolean('possui_pontos');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modos_jogo');
    }
};
