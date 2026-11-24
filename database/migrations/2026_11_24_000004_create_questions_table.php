<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * perguntas: enunciado, resposta correta (região), categoria e dificuldade. RN15
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('prompt', 255);
            $table->enum('difficulty', ['EASY', 'MEDIUM', 'HARD']);
            $table->foreignId('answer_region_id')->constrained('map_regions')->restrictOnDelete();
            $table->foreignId('phase_id')->constrained('phases')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('audio_path', 255)->nullable();     // faixa do DFPlayer, ex.: mp3/0100.mp3
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
