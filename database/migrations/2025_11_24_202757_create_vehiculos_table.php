<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->unique();
            $table->string('nombre_api')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('clase', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('tipo', 100)->nullable();
            $table->smallInteger('anio')->nullable();
            $table->string('placas', 50)->nullable();
            $table->string('area_asignada', 150)->nullable();
            $table->string('responsable', 150)->nullable();
            $table->string('gerencia_asignada', 150)->nullable();
            $table->timestamps();

            $table->index('placas');
            $table->index('area_asignada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
