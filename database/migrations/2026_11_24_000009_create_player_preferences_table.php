<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferências de acessibilidade (RN18): fonte maior, alto contraste, áudio e animações.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_preferences', function (Blueprint $table) {
            $table->foreignId('player_id')->primary()->constrained('players')->cascadeOnDelete();
            $table->boolean('large_font')->default(false);
            $table->boolean('high_contrast')->default(false);
            $table->boolean('audio_instructions')->default(true);
            $table->boolean('reduced_motion')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_preferences');
    }
};
