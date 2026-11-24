<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * conquistas (emblemas), progresso do jogador nelas e regiões já exploradas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 80);
            $table->string('description', 255);
            $table->string('icon', 30);
            $table->string('color', 20);
            // MATCHES_COMPLETED | TOTAL_POINTS | BEST_STREAK | COUNTRIES_EXPLORED | REGIONS_EXPLORED | PHASES_COMPLETED
            $table->string('rule', 40);
            $table->unsignedInteger('target');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('player_achievements', function (Blueprint $table) {
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->timestamp('unlocked_at')->useCurrent();
            $table->primary(['player_id', 'achievement_id']);
        });

        Schema::create('explored_regions', function (Blueprint $table) {
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('map_regions')->cascadeOnDelete();
            $table->timestamp('first_explored_at')->useCurrent();
            $table->primary(['player_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('explored_regions');
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('achievements');
    }
};
