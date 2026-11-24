<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * devices: mapas físicos (ESP32) conhecidos. game_events: fila de eventos
 * lida pelas telas (polling/SSE) — toques, respostas, conexão do mapa. RN20
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->string('device_id', 64)->primary();
            $table->string('name', 80)->nullable();
            $table->string('firmware_version', 40)->nullable();
            $table->boolean('sensors_ok')->default(true);
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->timestamp('last_seen_at', 3)->nullable();
            $table->foreignId('bound_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->boolean('announced_online')->default(false);
            $table->timestamps();
        });

        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('device_id', 64)->nullable()->index();
            $table->unsignedBigInteger('match_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('created_at', 3)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('devices');
    }
};
