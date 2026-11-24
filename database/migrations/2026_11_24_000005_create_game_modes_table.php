<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * modos_jogo: Exploração, Treino, Quiz, Desafio e Sala de Aula. RN08, RN09, RN12
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_modes', function (Blueprint $table) {
            $table->id();
            $table->enum('code', ['EXPLORATION', 'TRAINING', 'QUIZ', 'CHALLENGE', 'CLASSROOM'])->unique();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->boolean('is_timed')->default(false);
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->unsignedInteger('max_attempts')->nullable();        // NULL = ilimitado (treino). RN09
            $table->unsignedInteger('questions_per_match')->nullable();
            $table->boolean('is_scored')->default(true);
            $table->boolean('speed_bonus')->default(false);
            $table->boolean('streak_bonus')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_modes');
    }
};
