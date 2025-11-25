<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parametrizaciones_km', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50);    // 'semana', 'mes_total', 'mes_prom'
            $table->string('nombre', 50);  // 'Poco uso', 'Medio uso', 'Alto uso'
            $table->decimal('km_min', 10, 2);
            $table->decimal('km_max', 10, 2);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['tipo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametrizaciones_km');
    }
};
