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
    Schema::create('Hardware_Sensor', function (Blueprint $table) {
        $table->id('id_sensor');
        $table->integer('codigo_pino');
        $table->string('status_sensor', 50);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hardware_sensors');
    }
};
