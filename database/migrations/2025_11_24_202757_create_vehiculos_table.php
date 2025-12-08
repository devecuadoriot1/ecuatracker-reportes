<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->nullable()->unique();
            $table->unsignedBigInteger('codigo')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('group_title', 150)->nullable();
            $table->string('imei', 50)->nullable();
            $table->string('nombre_api')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('clase', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('tipo', 100)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('placas', 50)->nullable();
            $table->string('area_asignada', 150)->nullable();
            $table->string('responsable', 150)->nullable();
            $table->string('gerencia_asignada', 150)->nullable();

            $table->timestamps();

            // Índices útiles para búsquedas en reportes
            $table->index('placas');
            $table->index('codigo');
            $table->unique('imei');
            $table->index('group_id');
            $table->index('group_title');
            $table->index('area_asignada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
