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
    Schema::create('Categoria', function (Blueprint $table) {
        $table->id('id_categoria');
        $table->unsignedBigInteger('id_partida');
        $table->string('nome_categoria', 100);

        $table->foreign('id_partida')->references('id_partida')->on('Partida')->onDelete('cascade')->onUpdate('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
