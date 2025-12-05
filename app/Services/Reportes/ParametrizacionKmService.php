<?php

declare(strict_types=1);

namespace App\Services\Reportes;

use App\Models\ParametrizacionKm;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParametrizacionKmService
{
    /**
     * Cache por request.
     *
     * @var array<string,Collection<int,ParametrizacionKm>>
     */
    protected array $cache = [];

    /**
     * Clasifica un valor de km según el tipo.
     *
     * @param string $tipo 'semana' | 'mes_total' | 'mes_prom'
     */
    public function clasificar(string $tipo, float $km): string
    {
        // mes_prom reutiliza los rangos de semana
        $tipoReal = $tipo === ParametrizacionKm::TIPO_MES_PROM
            ? ParametrizacionKm::TIPO_SEMANA
            : $tipo;

        // Normalizar km a 2 decimales y evitar negativos
        $kmNormalizado = \round($km, 2);
        if ($kmNormalizado < 0.0) {
            $kmNormalizado = 0.0;
        }

        $rangos = $this->getRangosPorTipo($tipoReal);

        /** @var ParametrizacionKm|null $match */
        $match = $rangos->first(
            fn(ParametrizacionKm $rango): bool =>
            $kmNormalizado >= (float) $rango->km_min && $kmNormalizado <= (float) $rango->km_max
        );

        return $match?->nombre ?? 'SIN PARAMETRIZAR';
    }

    /**
     * Datos preparados para rellenar el formulario del modal.
     *
     * @return array{
     *     semana: array<int,array{nombre:string,km_min:float,km_max:float,orden:int}>,
     *     mes_total: array<int,array{nombre:string,km_min:float,km_max:float,orden:int}>
     * }
     */
    public function getRangosParaFormulario(): array
    {
        // Aseguramos que existan defaults en BD
        $this->ensureDefaults(ParametrizacionKm::TIPO_SEMANA);
        $this->ensureDefaults(ParametrizacionKm::TIPO_MES_TOTAL);

        $semana = ParametrizacionKm::query()
            ->tipo(ParametrizacionKm::TIPO_SEMANA)
            ->orderBy('orden')
            ->get()
            ->map(fn(ParametrizacionKm $r) => [
                'nombre' => $r->nombre,
                'km_min' => $r->km_min,
                'km_max' => $r->km_max,
                'orden'  => $r->orden,
            ])
            ->values()
            ->toArray();

        $mesTotal = ParametrizacionKm::query()
            ->tipo(ParametrizacionKm::TIPO_MES_TOTAL)
            ->orderBy('orden')
            ->get()
            ->map(fn(ParametrizacionKm $r) => [
                'nombre' => $r->nombre,
                'km_min' => $r->km_min,
                'km_max' => $r->km_max,
                'orden'  => $r->orden,
            ])
            ->values()
            ->toArray();

        return [
            'semana'    => $semana,
            'mes_total' => $mesTotal,
        ];
    }

    /**
     * Actualiza los rangos a partir de los datos enviados desde el formulario del modal.
     *
     * @param array{
     *     semana?: array<int,array<string,mixed>>,
     *     mes_total?: array<int,array<string,mixed>>,
     * } $data
     */
    public function actualizarRangosDesdeFormulario(array $data): void
    {
        $semana   = $data['semana'] ?? [];
        $mesTotal = $data['mes_total'] ?? [];

        DB::transaction(function () use ($semana, $mesTotal): void {
            // Borramos lo existente para estos tipos
            ParametrizacionKm::query()
                ->whereIn('tipo', [ParametrizacionKm::TIPO_SEMANA, ParametrizacionKm::TIPO_MES_TOTAL])
                ->delete();

            $this->crearRangosDesdeArray($semana, ParametrizacionKm::TIPO_SEMANA);
            $this->crearRangosDesdeArray($mesTotal, ParametrizacionKm::TIPO_MES_TOTAL);
        });

        // Limpiamos cache interno para que en la misma request no se queden valores antiguos
        unset($this->cache[ParametrizacionKm::TIPO_SEMANA], $this->cache[ParametrizacionKm::TIPO_MES_TOTAL]);

        Log::info('[ParametrizacionKm] Rangos actualizados desde formulario', [
            'semana_count'    => \count($semana),
            'mes_total_count' => \count($mesTotal),
        ]);
    }

    // -------------------------------------------------------------------------
    // Internos
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int,ParametrizacionKm>
     */
    protected function getRangosPorTipo(string $tipo): Collection
    {
        if (! isset($this->cache[$tipo])) {
            $rangos = ParametrizacionKm::query()
                ->tipo($tipo)
                ->orderBy('orden')
                ->get();

            // Si la tabla está vacía para este tipo, creamos los rangos por defecto en BD
            if ($rangos->isEmpty()) {
                $rangos = $this->createDefaultRangos($tipo);
            }

            $this->cache[$tipo] = $rangos;
        }

        return $this->cache[$tipo];
    }

    /**
     * Asegura que existan rangos por defecto en BD para el tipo dado.
     */
    protected function ensureDefaults(string $tipo): void
    {
        $rangos = ParametrizacionKm::query()
            ->tipo($tipo)
            ->limit(1)
            ->get();

        if ($rangos->isEmpty()) {
            $this->createDefaultRangos($tipo);
        }
    }

    /**
     * Crea en BD los rangos por defecto para el tipo dado (si existe configuración).
     *
     * @return Collection<int,ParametrizacionKm>
     */
    protected function createDefaultRangos(string $tipo): Collection
    {
        $rows = $this->defaultConfig($tipo);

        if ($rows === []) {
            return collect();
        }

        foreach ($rows as $row) {
            ParametrizacionKm::firstOrCreate(
                [
                    'tipo'   => $tipo,
                    'nombre' => $row['nombre'],
                ],
                [
                    'km_min' => $row['km_min'],
                    'km_max' => $row['km_max'],
                    'orden'  => $row['orden'],
                ]
            );
        }

        return ParametrizacionKm::query()
            ->tipo($tipo)
            ->orderBy('orden')
            ->get();
    }

    /**
     * Configuración por defecto (lo que antes tenías "quemado").
     *
     * @return array<int,array{nombre:string,km_min:float,km_max:float,orden:int}>
     */
    protected function defaultConfig(string $tipo): array
    {
        if ($tipo === ParametrizacionKm::TIPO_SEMANA) {
            return [
                ['nombre' => 'POCO USO',  'km_min' => 0.00,    'km_max' => 375.00,  'orden' => 1],
                ['nombre' => 'MEDIO USO', 'km_min' => 375.01,  'km_max' => 750.00,  'orden' => 2],
                ['nombre' => 'ALTO USO',  'km_min' => 750.01,  'km_max' => 1375.00, 'orden' => 3],
            ];
        }

        if ($tipo === ParametrizacionKm::TIPO_MES_TOTAL) {
            return [
                ['nombre' => 'POCO USO',  'km_min' => 0.00,    'km_max' => 1500.00, 'orden' => 1],
                ['nombre' => 'MEDIO USO', 'km_min' => 1500.01, 'km_max' => 3500.00, 'orden' => 2],
                ['nombre' => 'ALTO USO',  'km_min' => 3500.01, 'km_max' => 5500.00, 'orden' => 3],
            ];
        }

        // mes_prom NO tiene configuración propia: usa semana
        return [];
    }

    /**
     * Crea rangos a partir de un array "plano" (datos del formulario).
     *
     * @param array<int,array<string,mixed>> $rows
     */
    protected function crearRangosDesdeArray(array $rows, string $tipo): void
    {
        foreach ($rows as $row) {
            $nombre = trim((string) ($row['nombre'] ?? ''));

            if ($nombre === '') {
                continue; // ignorar filas vacías
            }

            $kmMin = (float) ($row['km_min'] ?? 0);
            $kmMax = (float) ($row['km_max'] ?? 0);
            $orden = (int) ($row['orden'] ?? 0);

            // Validación básica: km_min <= km_max
            if ($kmMin > $kmMax) {
                [$kmMin, $kmMax] = [$kmMax, $kmMin];
            }

            ParametrizacionKm::create([
                'tipo'   => $tipo,
                'nombre' => $nombre,
                'km_min' => $kmMin,
                'km_max' => $kmMax,
                'orden'  => $orden,
            ]);
        }
    }
}
