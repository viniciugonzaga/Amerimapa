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
    Schema::create('Tentativa_Resposta', function (Blueprint $table) {
        $table->id('id_tentativa');
        $table->unsignedBigInteger('id_regiao');
        $table->unsignedBigInteger('id_pergunta');
        $table->integer('pontos_ganhos')->default(0);
        $table->boolean('acertou');
        $table->integer('tempo_gasto_segundos');

        $table->foreign('id_regiao')->references('id_regiao')->on('Regiao_Mapa')->onDelete('cascade')->onUpdate('cascade');
        $table->foreign('id_pergunta')->references('id_pergunta')->on('Pergunta')->onDelete('cascade')->onUpdate('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tentativas_respostas');
    }
};
