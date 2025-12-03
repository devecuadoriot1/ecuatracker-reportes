<?php

namespace App\Filament\Widgets;

use App\Models\Vehiculo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehiculosOverview extends BaseWidget
{
    protected ?string $pollingInterval = '60s'; // refresco opcional

    protected function getStats(): array
    {
        $total = Vehiculo::count();
        $sinPlaca = Vehiculo::whereNull('placas')->count();

        return [
            Stat::make('Vehículos registrados', $total),
            Stat::make('Vehículos sin placas', $sinPlaca),
            // aquí luego puedes añadir métricas de tus otros reportes
        ];
    }
}
