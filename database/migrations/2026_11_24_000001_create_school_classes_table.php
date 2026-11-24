<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * turmas: usadas no painel do professor e no Modo Sala de Aula.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('grade', 40)->nullable();          // ex.: "4º ano"
            $table->string('color', 20)->default('blue');
            $table->foreignId('teacher_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->foreignId('school_class_id')->nullable()->after('avatar')
                ->constrained('school_classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_class_id');
        });
        Schema::dropIfExists('school_classes');
    }
};
