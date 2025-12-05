<?php

declare(strict_types=1);

namespace App\Services\Vehiculos;

use App\Models\Vehiculo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ImportDatosVehiculosService
{
    /**
     * Procesa las filas del Excel y actualiza los "datos extra" de los vehículos.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{
     *     total_rows:int,
     *     actualizados:int,
     *     sin_codigo:int,
     *     no_encontrados:int,
     *     errores:int,
     *     errores_detalle:array<int,array{row:int,message:string}>
     * }
     */
    public function importar(Collection $rows): array
    {
        $stats = [
            'total_rows'      => 0,
            'actualizados'    => 0,
            'sin_codigo'      => 0,
            'no_encontrados'  => 0,
            'errores'         => 0,
            'errores_detalle' => [],
        ];

        foreach ($rows as $index => $row) {
            $stats['total_rows']++;

            try {
                $codigo = isset($row['cod']) ? trim((string) $row['cod']) : '';

                if ($codigo === '') {
                    $stats['sin_codigo']++;
                    continue;
                }

                // Buscamos por "codigo" (no por device_id).
                $vehiculo = Vehiculo::query()
                    ->where('codigo', (int) $codigo)
                    ->first();

                if (! $vehiculo) {
                    $stats['no_encontrados']++;
                    continue;
                }

                // Mapeo de columnas Excel -> campos del modelo
                $marca       = $this->cleanString($row['marca'] ?? null);
                $clase       = $this->cleanString($row['clase'] ?? null);
                $modelo      = $this->cleanString($row['modelo'] ?? null);
                $tipo        = $this->cleanString($row['tipo'] ?? null);
                $anioRaw     = $row['ano'] ?? null;
                $placas      = $this->cleanString($row['placas'] ?? null);
                $area        = $this->cleanString($row['area'] ?? null);
                $responsable = $this->cleanString($row['responsable'] ?? null);
                $gerencia    = $this->cleanString($row['gerencia_general'] ?? null);

                $anio = null;
                if ($anioRaw !== null && $anioRaw !== '') {
                    $anioInt = (int) $anioRaw;
                    $anio    = $anioInt > 0 ? $anioInt : null;
                }

                // Solo actualizamos campos "extra" (no tocamos device_id, imei, etc.)
                $vehiculo->fill([
                    'marca'            => $marca ?: $vehiculo->marca,
                    'clase'            => $clase ?: $vehiculo->clase,
                    'modelo'           => $modelo ?: $vehiculo->modelo,
                    'tipo'             => $tipo ?: $vehiculo->tipo,
                    'anio'             => $anio ?? $vehiculo->anio,
                    'placas'           => $placas ?: $vehiculo->placas,
                    'area_asignada'    => $area ?: $vehiculo->area_asignada,
                    'responsable'      => $responsable ?: $vehiculo->responsable,
                    'gerencia_asignada' => $gerencia ?: $vehiculo->gerencia_asignada,
                ]);

                $vehiculo->save();

                $stats['actualizados']++;
            } catch (\Throwable $e) {
                $stats['errores']++;
                $stats['errores_detalle'][] = [
                    'row'     => $index + 2, // +2 porque fila 1 = encabezados
                    'message' => $e->getMessage(),
                ];

                Log::error('Error al importar datos de vehículo desde Excel', [
                    'row'   => $index + 2,
                    'data'  => $row,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Limpia strings de forma segura (trim + convierte vacío a null).
     */
    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
