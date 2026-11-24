<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * regioes_mapa: toda área tocável (ou agrupadora) do mapa físico. RN05, RN19, RN22
 * hardware_pin = "<endereço I2C do MPR121>:<eletrodo>", ex.: "5A:03" (único).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('hardware_pin', 20)->nullable()->unique();
            $table->enum('type', ['REGION', 'COUNTRY', 'STATE']);
            $table->foreignId('parent_id')->nullable()->constrained('map_regions')->restrictOnDelete();
            $table->string('iso_code', 3)->nullable()->unique();   // liga a região ao desenho SVG
            $table->string('capital', 120)->nullable();
            $table->string('language', 120)->nullable();
            $table->string('short_description', 255)->nullable();
            $table->text('info')->nullable();
            $table->timestamps();

            $table->unique(['name', 'parent_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_regions');
    }
};
