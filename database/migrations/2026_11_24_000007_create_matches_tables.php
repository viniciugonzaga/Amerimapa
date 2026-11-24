<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * partidas (RN01, RN02, RN03, RN10, RN14) e respostas_partidas (RN06, RN07, RN13):
 * uma linha por tentativa, telemetria detalhada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained('players')->cascadeOnDelete(); // NULL = visitante
            $table->uuid('guest_id')->nullable()->index();
            $table->foreignId('mode_id')->constrained('game_modes')->restrictOnDelete();
            $table->foreignId('phase_id')->constrained('phases')->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->enum('status', ['IN_PROGRESS', 'COMPLETED', 'ABANDONED'])->default('IN_PROGRESS')->index();
            $table->integer('total_score')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->unsignedInteger('streak')->default(0);
            $table->unsignedInteger('best_streak')->default(0);
            $table->unsignedInteger('answered_questions')->default(0);
            // Apenas uma pergunta ativa por partida. RN03
            $table->foreignId('active_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->timestamp('question_started_at', 3)->nullable();
            $table->unsignedInteger('attempts_on_question')->default(0);
            $table->timestamp('started_at', 3)->useCurrent();
            $table->timestamp('ended_at', 3)->nullable();
        });

        Schema::create('match_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->foreignId('touched_region_id')->nullable()->constrained('map_regions')->nullOnDelete();
            $table->enum('result', ['CORRECT', 'WRONG', 'TIMEOUT', 'SKIPPED']);
            $table->unsignedInteger('attempt_number');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->integer('points_earned')->default(0);
            $table->enum('source', ['MAP', 'SCREEN'])->default('SCREEN');
            $table->timestamp('answered_at', 3)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_answers');
        Schema::dropIfExists('matches');
    }
};
