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
    Schema::create('Audio_Feedback', function (Blueprint $table) {
        $table->id('id_audio');
        $table->string('tipo_audio', 50);
        $table->integer('codigo_arquivo_dfplayer');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_feedbacks');
    }
};
