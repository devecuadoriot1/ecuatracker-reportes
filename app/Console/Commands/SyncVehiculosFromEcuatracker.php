<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ecuatracker\SyncVehiculosService;
use Illuminate\Console\Command;

class SyncVehiculosFromEcuatracker extends Command
{
    protected $signature = 'ecuatracker:sync-vehiculos {--dry-run : Muestra lo que haría sin guardar cambios}';

    protected $description = 'Sincroniza todos los dispositivos desde Ecuatracker a la tabla vehiculos';

    public function handle(SyncVehiculosService $syncVehiculosService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Sincronizando vehículos desde Ecuatracker...');
        if ($dryRun) {
            $this->warn('Modo dry-run: no se guardarán cambios en la base de datos.');
        }

        $result = $syncVehiculosService->sync($dryRun);

        $this->info(sprintf(
            'Sincronización finalizada. Creados: %d, Actualizados: %d',
            $result['created'],
            $result['updated']
        ));

        return self::SUCCESS;
    }
}
