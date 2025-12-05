<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParametrizacionKm;

class ParametrizacionesKmSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia solo estos tipos (opcional)
        ParametrizacionKm::whereIn('tipo', ['semana', 'mes_total'])->delete();

        $rows = [
            // SEMANAL
            ['tipo' => 'semana', 'nombre' => 'POCO USO',  'km_min' => 0.00,    'km_max' => 375.00,  'orden' => 1],
            ['tipo' => 'semana', 'nombre' => 'MEDIO USO', 'km_min' => 375.01,  'km_max' => 750.00,  'orden' => 2],
            ['tipo' => 'semana', 'nombre' => 'ALTO USO',  'km_min' => 750.01,  'km_max' => 1375.00, 'orden' => 3],

            // MENSUAL - TOTAL
            ['tipo' => 'mes_total', 'nombre' => 'POCO USO',  'km_min' => 0.00,    'km_max' => 1500.00, 'orden' => 1],
            ['tipo' => 'mes_total', 'nombre' => 'MEDIO USO', 'km_min' => 1500.01, 'km_max' => 3500.00, 'orden' => 2],
            ['tipo' => 'mes_total', 'nombre' => 'ALTO USO',  'km_min' => 3500.01, 'km_max' => 5500.00, 'orden' => 3],
        ];

        ParametrizacionKm::insert($rows);
    }
}
