<?php

declare(strict_types=1);

namespace App\Services\Reportes;

use App\Models\Vehiculo;
use App\Services\Ecuatracker\EcuatrackerClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnalisisRecorridoService
{
    protected readonly bool $debug;
    protected int $chunkSize = 100; // Límite de devices por petición para evitar URLs enormes

    public function __construct(
        protected readonly EcuatrackerClient $ecuatrackerClient,
        protected readonly ParametrizacionKmService $parametrizacionKmService,
    ) {
        $this->debug = (bool) config('app.debug', false);
    }

    /**
     * REPORTE SEMANAL + MES (detalle por semanas).
     *
     * @param array<int,int> $deviceIds
     * @return array<int,array<string,mixed>>
     */
    public function generar(array $deviceIds, Carbon $desde, Carbon $hasta, string $titulo): array
    {
        if ($deviceIds === []) {
            return [];
        }

        if ($this->debug) {
            Log::info('[AnalisisRecorrido] (SEMANAL) Iniciando generación', [
                'device_ids' => $deviceIds,
                'desde'      => $desde->toIso8601String(),
                'hasta'      => $hasta->toIso8601String(),
                'titulo'     => $titulo,
            ]);
        }

        $semanas = $this->dividirEnSemanas($desde, $hasta);

        // Cargar vehículos una sola vez (menos queries)
        $vehiculosIndex = $this->buildVehiculosIndex($deviceIds);

        $kmSemanalPorDispositivo = $this->obtenerKmSemanalPorDispositivo(
            $deviceIds,
            $semanas,
            $titulo,
            $desde,
            $hasta,
            $vehiculosIndex['mapNombreToDeviceId']
        );

        /** @var array<int,Vehiculo> $vehiculosExtras */
        $vehiculosExtras = $vehiculosIndex['porDeviceId'];

        $resultado   = [];
        $numSemanas  = \count($semanas) ?: 1;
        $mesLabel = mb_strtoupper($desde->locale('es')->translatedFormat('F'), 'UTF-8');

        foreach ($deviceIds as $deviceId) {
            $info      = $kmSemanalPorDispositivo[$deviceId] ?? ['nombre_api' => null, 'semanas' => []];
            $nombreApi = $info['nombre_api'];
            $kmSemanas = $info['semanas'];

            $kmTotalMes = array_sum(array_map('floatval', $kmSemanas));
            $kmPromedio = $kmTotalMes / $numSemanas;

            $paramMesTotal = $this->parametrizacionKmService->clasificar('mes_total', $kmTotalMes);
            $paramMesProm  = $this->parametrizacionKmService->clasificar('mes_prom', $kmPromedio);
            $conclusionMes = $paramMesProm;

            /** @var Vehiculo|null $extra */
            $extra = $vehiculosExtras[$deviceId] ?? null;

            $filasSemanas   = [];
            $contadorSemana = 1;

            foreach ($semanas as $idx => $semana) {
                $kmSemana    = (float) ($kmSemanas[$idx] ?? 0.0);
                $paramSemana = $this->parametrizacionKmService->clasificar('semana', $kmSemana);

                $filasSemanas[] = [
                    'numero'          => $contadorSemana,
                    'desde'           => $semana['desde']->toDateString(),
                    'hasta'           => $semana['hasta']->toDateString(),
                    'km'              => $kmSemana,
                    'parametrizacion' => $paramSemana,
                ];

                $contadorSemana++;
            }

            $resultado[] = [
                'device_id'          => $deviceId,
                'codigo'             => $extra?->codigo,
                'nombre_api'         => $nombreApi ?? $extra?->nombre_api,
                'marca'              => $extra?->marca,
                'clase'              => $extra?->clase,
                'modelo'             => $extra?->modelo,
                'tipo'               => $extra?->tipo,
                'anio'               => $extra?->anio,
                'placas'             => $extra?->placas,
                'area_asignada'      => $extra?->area_asignada,
                'responsable'        => $extra?->responsable,
                'gerencia_asignada'  => $extra?->gerencia_asignada,
                'mes_label'          => $mesLabel,
                'semanas'            => $filasSemanas,
                'km_total_mes'       => $kmTotalMes,
                'param_mes_total'    => $paramMesTotal,
                'km_promedio_mes'    => $kmPromedio,
                'param_mes_promedio' => $paramMesProm,
                'conclusion_mes'     => $conclusionMes,
            ];
        }

        return $resultado;
    }

    /**
     * REPORTE MENSUAL GENERAL.
     *
     * @param array<int,int> $deviceIds
     * @return array<int,array<string,mixed>>
     */
    public function generarMensual(array $deviceIds, Carbon $desde, Carbon $hasta, string $titulo): array
    {
        if ($deviceIds === []) {
            return [];
        }

        if ($this->debug) {
            Log::info('[AnalisisRecorrido] (MENSUAL) Iniciando generación', [
                'device_ids' => $deviceIds,
                'desde'      => $desde->toIso8601String(),
                'hasta'      => $hasta->toIso8601String(),
                'titulo'     => $titulo,
            ]);
        }

        // Cargar vehículos una sola vez
        $vehiculosIndex = $this->buildVehiculosIndex($deviceIds);

        $response = $this->generateKmReportChunked(
            $deviceIds,
            $desde->toDateString(),
            $hasta->toDateString(),
            [
                'title'     => $titulo,
                'from_time' => $desde->format('H:i:s'),
                'to_time'   => $hasta->format('H:i:s'),
            ]
        );

        $kmPorDispositivo = $this->extractKmPorDispositivo(
            $response,
            $deviceIds,
            $vehiculosIndex['mapNombreToDeviceId']
        );

        $semanas    = $this->dividirEnSemanas($desde, $hasta);
        $numSemanas = \count($semanas) ?: 1;

        /** @var array<int,Vehiculo> $vehiculosExtras */
        $vehiculosExtras = $vehiculosIndex['porDeviceId'];
        $mesLabel = mb_strtoupper($desde->locale('es')->translatedFormat('F'), 'UTF-8');

        $resultado = [];

        foreach ($deviceIds as $deviceId) {
            $info      = $kmPorDispositivo[$deviceId] ?? ['km_total' => 0.0, 'nombre_api' => null];
            $kmTotal   = (float) ($info['km_total'] ?? 0.0);
            $nombreApi = $info['nombre_api'] ?? null;

            $kmPromedio = $kmTotal / $numSemanas;

            $paramMesTotal = $this->parametrizacionKmService->clasificar('mes_total', $kmTotal);
            $paramMesProm  = $this->parametrizacionKmService->clasificar('mes_prom', $kmPromedio);
            $conclusionMes = $paramMesProm;

            /** @var Vehiculo|null $extra */
            $extra = $vehiculosExtras[$deviceId] ?? null;

            $resultado[] = [
                'device_id'          => $deviceId,
                'codigo'             => $extra?->codigo,
                'nombre_api'         => $nombreApi ?? $extra?->nombre_api,
                'marca'              => $extra?->marca,
                'clase'              => $extra?->clase,
                'modelo'             => $extra?->modelo,
                'tipo'               => $extra?->tipo,
                'anio'               => $extra?->anio,
                'placas'             => $extra?->placas,
                'area_asignada'      => $extra?->area_asignada,
                'responsable'        => $extra?->responsable,
                'gerencia_asignada'  => $extra?->gerencia_asignada,
                'mes_label'          => $mesLabel,
                'km_total_mes'       => $kmTotal,
                'param_mes_total'    => $paramMesTotal,
                'km_promedio_mes'    => $kmPromedio,
                'param_mes_promedio' => $paramMesProm,
                'conclusion_mes'     => $conclusionMes,
            ];
        }

        return $resultado;
    }

    /**
     * Construye índices de Vehiculo para evitar múltiples queries.
     *
     * @param array<int,int> $deviceIds
     * @return array{
     *     porDeviceId: array<int,Vehiculo>,
     *     mapNombreToDeviceId: array<string,int>
     * }
     */
    protected function buildVehiculosIndex(array $deviceIds): array
    {
        $vehiculos = Vehiculo::whereIn('device_id', $deviceIds)->get();

        /** @var array<int,Vehiculo> $porDeviceId */
        $porDeviceId = $vehiculos->keyBy('device_id')->all();

        $mapNombreToDeviceId = [];
        foreach ($vehiculos as $vehiculo) {
            if ($vehiculo->nombre_api) {
                $mapNombreToDeviceId[$vehiculo->nombre_api] = (int) $vehiculo->device_id;
            }
        }

        return [
            'porDeviceId'         => $porDeviceId,
            'mapNombreToDeviceId' => $mapNombreToDeviceId,
        ];
    }

    /**
     * Divide el rango en semanas lógicas:
     * - Hasta 7 días  -> 1 semana
     * - Hasta 14 días -> 2 semanas
     * - Hasta 21 días -> 3 semanas
     * - Más de 21 días (p.ej. meses de 29,30,31 días) -> 4 semanas:
     *   * Semanas 1,2,3 de 7 días (siempre que alcance)
     *   * Semana 4 con todos los días restantes
     *
     * @return array<int,array{desde:Carbon,hasta:Carbon}>
     */
    protected function dividirEnSemanas(Carbon $desde, Carbon $hasta): array
    {
        $semanas   = [];

        $inicioMes = $desde->copy()->startOfDay();
        $finMes    = $hasta->copy()->endOfDay();

        if ($inicioMes->gt($finMes)) {
            return $semanas;
        }

        $dias = (int) ($inicioMes->diffInDays($finMes) + 1);

        // 1 semana
        if ($dias <= 7) {
            $semanas[] = ['desde' => $inicioMes, 'hasta' => $finMes];
            return $semanas;
        }

        // 2 semanas: primera de hasta 7 días, segunda el resto
        if ($dias <= 14) {
            $inicio = $inicioMes->copy();

            $finSemana1 = $inicio->copy()->addDays(6)->endOfDay();
            if ($finSemana1->gt($finMes)) {
                $finSemana1 = $finMes->copy();
            }

            $semanas[] = ['desde' => $inicio, 'hasta' => $finSemana1];

            $inicioSemana2 = $finSemana1->copy()->addDay()->startOfDay();
            if ($inicioSemana2->lte($finMes)) {
                $semanas[] = ['desde' => $inicioSemana2, 'hasta' => $finMes];
            }

            return $semanas;
        }

        // 3 semanas: dos primeras de hasta 7 días, la tercera con el resto
        if ($dias <= 21) {
            $inicio = $inicioMes->copy();

            // Semana 1
            $finSemana1 = $inicio->copy()->addDays(6)->endOfDay();
            if ($finSemana1->gt($finMes)) {
                $finSemana1 = $finMes->copy();
            }
            $semanas[] = ['desde' => $inicio, 'hasta' => $finSemana1];

            // Semana 2
            $inicio = $finSemana1->copy()->addDay()->startOfDay();
            if ($inicio->gt($finMes)) {
                return $semanas;
            }

            $finSemana2 = $inicio->copy()->addDays(6)->endOfDay();
            if ($finSemana2->gt($finMes)) {
                $finSemana2 = $finMes->copy();
            }
            $semanas[] = ['desde' => $inicio, 'hasta' => $finSemana2];

            // Semana 3: todo lo que queda
            $inicio = $finSemana2->copy()->addDay()->startOfDay();
            if ($inicio->lte($finMes)) {
                $semanas[] = ['desde' => $inicio, 'hasta' => $finMes];
            }

            return $semanas;
        }

        // Más de 21 días:
        // Siempre 4 semanas máximo
        // Semanas 1,2,3 de 7 días (si hay días suficientes)
        // Semana 4 con el resto (incluye días 29,30,31, etc.)
        $inicio = $inicioMes->copy();

        // Semana 1
        $finSemana1 = $inicio->copy()->addDays(6)->endOfDay();
        if ($finSemana1->gt($finMes)) {
            $finSemana1 = $finMes->copy();
        }
        $semanas[] = ['desde' => $inicio, 'hasta' => $finSemana1];

        // Semana 2
        $inicio = $finSemana1->copy()->addDay()->startOfDay();
        if ($inicio->gt($finMes)) {
            return $semanas;
        }

        $finSemana2 = $inicio->copy()->addDays(6)->endOfDay();
        if ($finSemana2->gt($finMes)) {
            $finSemana2 = $finMes->copy();
        }
        $semanas[] = ['desde' => $inicio, 'hasta' => $finSemana2];

        // Semana 3
        $inicio = $finSemana2->copy()->addDay()->startOfDay();
        if ($inicio->gt($finMes)) {
            return $semanas;
        }

        $finSemana3 = $inicio->copy()->addDays(6)->endOfDay();
        if ($finSemana3->gt($finMes)) {
            $finSemana3 = $finMes->copy();
        }
        $semanas[] = ['desde' => $inicio, 'hasta' => $finSemana3];

        // Semana 4: todo lo que reste hasta finMes
        $inicio = $finSemana3->copy()->addDay()->startOfDay();
        if ($inicio->lte($finMes)) {
            $semanas[] = ['desde' => $inicio, 'hasta' => $finMes];
        }

        return $semanas;
    }

    /**
     * @param array<int,int> $deviceIds
     * @param array<int,array{desde:Carbon,hasta:Carbon}> $semanas
     * @param array<string,int> $mapNombreToDeviceId
     * @return array<int,array{nombre_api:?string,semanas:array<int,float>}>
     */
    protected function obtenerKmSemanalPorDispositivo(
        array $deviceIds,
        array $semanas,
        string $titulo,
        Carbon $desdeOriginal,
        Carbon $hastaOriginal,
        array $mapNombreToDeviceId
    ): array {
        $resultado = [];

        foreach ($deviceIds as $deviceId) {
            $resultado[$deviceId] = [
                'nombre_api' => null,
                'semanas'    => [],
            ];
        }

        foreach ($semanas as $idx => $semana) {
            $desdeSemanaDate = $semana['desde']->toDateString();
            $hastaSemanaDate = $semana['hasta']->toDateString();

            $fromTime = '00:00:00';
            $toTime   = '23:59:59';

            if ($idx === 0 && $semana['desde']->isSameDay($desdeOriginal)) {
                $fromTime = $desdeOriginal->format('H:i:s');
            }

            $isLast = $idx === array_key_last($semanas);
            if ($isLast && $semana['hasta']->isSameDay($hastaOriginal)) {
                $toTime = $hastaOriginal->format('H:i:s');
            }

            if ($this->debug) {
                Log::info('[AnalisisRecorrido] Llamando API para semana', [
                    'idx'       => $idx,
                    'desde'     => $desdeSemanaDate,
                    'hasta'     => $hastaSemanaDate,
                    'from_time' => $fromTime,
                    'to_time'   => $toTime,
                ]);
            }

            $responseSemana = $this->generateKmReportChunked(
                $deviceIds,
                $desdeSemanaDate,
                $hastaSemanaDate,
                [
                    'title'     => "{$titulo} - Semana " . ($idx + 1),
                    'from_time' => $fromTime,
                    'to_time'   => $toTime,
                ]
            );

            $kmSemanaPorDispositivo = $this->extractKmPorDispositivo(
                $responseSemana,
                $deviceIds,
                $mapNombreToDeviceId
            );

            foreach ($deviceIds as $deviceId) {
                $infoSemana = $kmSemanaPorDispositivo[$deviceId] ?? null;
                $kmSemana   = $infoSemana['km_total'] ?? 0.0;
                $nombreApi  = $infoSemana['nombre_api'] ?? null;

                $resultado[$deviceId]['semanas'][$idx] = (float) $kmSemana;

                if ($nombreApi && $resultado[$deviceId]['nombre_api'] === null) {
                    $resultado[$deviceId]['nombre_api'] = $nombreApi;
                }
            }
        }

        return $resultado;
    }

    /**
     * @param array<int,int> $deviceIds
     * @return array<int,Vehiculo>
     */
    protected function cargarVehiculosExtras(array $deviceIds): array
    {
        $vehiculos = Vehiculo::whereIn('device_id', $deviceIds)->get();

        return $vehiculos->keyBy('device_id')->all();
    }

    /**
     * Llama generate_report en chunks y combina los items.
     *
     * @param array<int,int> $deviceIds
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    protected function generateKmReportChunked(array $deviceIds, string $desde, string $hasta, array $options = []): array
    {
        if ($deviceIds === []) {
            return ['items' => []];
        }

        $mergedItems = [];
        $chunkIndex  = 1;

        foreach (\array_chunk($deviceIds, $this->chunkSize) as $chunk) {
            $opts = $options;
            if (isset($opts['title'])) {
                $opts['title'] = $opts['title'] . " (parte {$chunkIndex})";
            }

            $response = $this->ecuatrackerClient->generateKmReport($chunk, $desde, $hasta, $opts);

            if (isset($response['items']) && \is_array($response['items'])) {
                $mergedItems = \array_merge($mergedItems, $response['items']);
            }

            $chunkIndex++;
        }

        return ['items' => $mergedItems];
    }

    /**
     * @param array<string,mixed> $response
     * @param array<int,int>      $deviceIds
     * @param array<string,int>   $mapNombreToDeviceId
     * @return array<int,array{km_total:float,nombre_api:?string}>
     */
    protected function extractKmPorDispositivo(array $response, array $deviceIds, array $mapNombreToDeviceId): array
    {
        $resultado = [];

        $items = $response['items'] ?? [];
        if (!\is_array($items)) {
            $items = [];
        }

        if ($this->debug) {
            Log::info('[AnalisisRecorrido] extractKmPorDispositivo items count', [
                'items_count' => \count($items),
            ]);
        }

        // Conjunto de ids solicitados, para filtrar
        $deviceIdsSet = \array_flip($deviceIds);

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $meta   = $item['meta']   ?? [];
            $totals = $item['totals'] ?? [];

            if (!\is_array($meta)) {
                $meta = [];
            }
            if (!\is_array($totals)) {
                $totals = [];
            }

            // 1) Intentar obtener el device_id directo (por si algún reporte lo trae)
            $deviceIdRaw   = null;
            $candidatosId  = [
                $meta['device.id'] ?? null,
                $meta['device_id'] ?? null,
                $item['device_id'] ?? null,
                $item['id']        ?? null,
            ];

            foreach ($candidatosId as $c) {
                if ($c === null) {
                    continue;
                }

                if (\is_array($c)) {
                    $c = $c['value'] ?? null;
                }

                if ($c !== null && $c !== '' && \is_numeric($c)) {
                    $deviceIdRaw = (int) $c;
                    break;
                }
            }

            // 2) Nombre del dispositivo (device.name)
            $nombreApi = null;
            if (isset($meta['device.name'])) {
                $nameMeta  = $meta['device.name'];
                $nombreApi = \is_array($nameMeta) ? ($nameMeta['value'] ?? null) : $nameMeta;
            } elseif (isset($item['name'])) {
                $nombreApi = $item['name'];
            }

            // 3) Distancia (totals.distance.value)
            $distanceCandidate = null;

            if (isset($totals['distance']) && \is_array($totals['distance'])) {
                if (isset($totals['distance']['value'])) {
                    $distanceCandidate = $totals['distance']['value'];
                } else {
                    $distanceCandidate = \reset($totals['distance']);
                }
            } elseif (isset($totals['distance'])) {
                $distanceCandidate = $totals['distance'];
            } elseif (isset($item['distance'])) {
                $distanceCandidate = $item['distance'];
            } elseif (isset($item['total_distance'])) {
                $distanceCandidate = $item['total_distance'];
            }

            $distanceStr = (string) ($distanceCandidate ?? '0');
            $numericStr  = \preg_replace('/[^0-9\.\-]/', '', $distanceStr);
            $distanceKm  = (float) ($numericStr ?: 0);

            // 4) Si no pudimos obtener device_id directo, intentamos mapear por nombre_api
            if ($deviceIdRaw === null && $nombreApi !== null && isset($mapNombreToDeviceId[$nombreApi])) {
                $deviceIdRaw = $mapNombreToDeviceId[$nombreApi];
            }

            // 5) Guardar sólo si el device_id está dentro de los solicitados
            if ($deviceIdRaw !== null && isset($deviceIdsSet[$deviceIdRaw])) {
                // Importante: aquí NO acumulamos, usamos un solo valor por device
                $resultado[$deviceIdRaw] = [
                    'km_total'   => $distanceKm,
                    'nombre_api' => $nombreApi,
                ];
            } elseif ($this->debug) {
                Log::warning('[AnalisisRecorrido] Item de reporte no se pudo mapear a device_id solicitado', [
                    'device_id_raw' => $deviceIdRaw,
                    'nombre_api'    => $nombreApi,
                    'distance_raw'  => $distanceCandidate,
                    'distance_km'   => $distanceKm,
                    'meta_keys'     => \array_keys($meta),
                    'totals_keys'   => \array_keys($totals),
                ]);
            }
        }

        if ($this->debug) {
            $faltantes = \array_diff($deviceIds, \array_keys($resultado));
            Log::info('[AnalisisRecorrido] extractKmPorDispositivo resumen', [
                'mapeados'  => \count($resultado),
                'faltantes' => $faltantes,
            ]);
        }

        return $resultado;
    }
}
