<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * atividades: missões montadas pelo professor (conjunto de perguntas + modo + fase),
 * opcionalmente atribuídas a uma turma. RN22: conteúdo expansível sem mudar a estrutura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('description', 255)->nullable();
            $table->foreignId('phase_id')->constrained('phases')->restrictOnDelete();
            $table->foreignId('mode_id')->constrained('game_modes')->restrictOnDelete();
            $table->foreignId('focus_region_id')->nullable()->constrained('map_regions')->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedInteger('estimated_minutes')->default(5);
            $table->unsignedInteger('reward_points')->default(100);
            $table->string('icon', 30)->default('map');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_question', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->primary(['activity_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_question');
        Schema::dropIfExists('activities');
    }
};
