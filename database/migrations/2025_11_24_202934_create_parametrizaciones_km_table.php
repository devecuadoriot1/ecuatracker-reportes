<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parametrizaciones_km', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50);
            $table->string('nombre', 50);
            $table->decimal('km_min', 8, 2);
            $table->decimal('km_max', 8, 2);
            $table->unsignedInteger('orden')->default(0);

            $table->timestamps();

            $table->index(['tipo', 'orden']);
            $table->unique(['tipo', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametrizaciones_km');
    }
};
