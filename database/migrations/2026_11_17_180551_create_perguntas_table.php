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
    Schema::create('Pergunta', function (Blueprint $table) {
        $table->id('id_pergunta');
        $table->unsignedBigInteger('id_categoria');
        $table->unsignedBigInteger('id_fase')->nullable();
        $table->text('enunciado');
        $table->string('dificuldade', 50);
        $table->string('caminho_audio', 255)->nullable();

        $table->foreign('id_categoria')->references('id_categoria')->on('Categoria')->onDelete('cascade')->onUpdate('cascade');
        $table->foreign('id_fase')->references('id_fase')->on('Fase')->onDelete('set null')->onUpdate('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perguntas');
    }
};
