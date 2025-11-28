<?php

declare(strict_types=1);

namespace App\Services\Reportes;

use App\Models\ParametrizacionKm;
use Illuminate\Support\Collection;

class ParametrizacionKmService
{
    /**
     * Cache por request.
     *
     * @var array<string,Collection>
     */
    protected array $cache = [];

    /**
     * @param string $tipo 'semana' | 'mes_total' | 'mes_prom'
     */
    public function clasificar(string $tipo, float $km): ?string
    {
        // Normalizar km a 2 decimales y evitar negativos
        $kmNormalizado = \round($km, 2);
        if ($kmNormalizado < 0.0) {
            $kmNormalizado = 0.0;
        }

        $rangos = $this->getRangosPorTipo($tipo);

        /** @var ParametrizacionKm|null $match */
        $match = $rangos->first(function (ParametrizacionKm $rango) use ($kmNormalizado): bool {
            return $kmNormalizado >= (float) $rango->km_min && $kmNormalizado <= (float) $rango->km_max;
        });

        if ($match !== null) {
            return $match->nombre;
        }

        // Si no hay coincidencia en ningún rango configurado
        return 'SIN PARAMETRIZAR';
    }

    protected function getRangosPorTipo(string $tipo): Collection
    {
        if (!isset($this->cache[$tipo])) {
            $rangos = ParametrizacionKm::where('tipo', $tipo)
                ->orderBy('orden')
                ->get();

            if ($rangos->isEmpty()) {
                $rangos = $this->defaultRangos($tipo);
            }

            $this->cache[$tipo] = $rangos;
        }

        return $this->cache[$tipo];
    }

    protected function defaultRangos(string $tipo): Collection
    {
        $rows = [];

        if ($tipo === 'semana') {
            $rows = [
                ['nombre' => 'POCO USO',  'km_min' => 0.00,    'km_max' => 375.00,  'orden' => 1],
                ['nombre' => 'MEDIO USO', 'km_min' => 375.01,  'km_max' => 750.00,  'orden' => 2],
                ['nombre' => 'ALTO USO',  'km_min' => 750.01,  'km_max' => 1375.00, 'orden' => 3],
            ];
        }

        if ($tipo === 'mes_total' || $tipo === 'mes_prom') {
            $rows = [
                ['nombre' => 'POCO USO',  'km_min' => 0.00,    'km_max' => 1500.00, 'orden' => 1],
                ['nombre' => 'MEDIO USO', 'km_min' => 1500.01, 'km_max' => 3500.00, 'orden' => 2],
                ['nombre' => 'ALTO USO',  'km_min' => 3500.01, 'km_max' => 5500.00, 'orden' => 3],
            ];
        }

        return collect(array_map(
            static function (array $row) use ($tipo): ParametrizacionKm {
                $model = new ParametrizacionKm();
                $model->tipo   = $tipo;
                $model->nombre = $row['nombre'];
                $model->km_min = $row['km_min'];
                $model->km_max = $row['km_max'];
                $model->orden  = $row['orden'];

                return $model;
            },
            $rows
        ));
    }
}
