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
    Schema::create('Partida', function (Blueprint $table) {
        $table->id('id_partida');
        $table->unsignedBigInteger('id_jogador');
        $table->unsignedBigInteger('id_modo');
        $table->dateTime('data_inicio');
        $table->integer('pontuacao_total')->default(0);
        $table->string('status_partida', 50);

        $table->foreign('id_jogador')->references('id_jogador')->on('Jogador')->onDelete('cascade')->onUpdate('cascade');
        $table->foreign('id_modo')->references('id_modo')->on('Modo_Jogo')->onDelete('cascade')->onUpdate('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidas');
    }
};
