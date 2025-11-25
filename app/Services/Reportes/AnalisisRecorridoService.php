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
        if ($this->debug) {
            Log::info('[AnalisisRecorrido] (SEMANAL) Iniciando generación', [
                'device_ids' => $deviceIds,
                'desde'      => $desde->toIso8601String(),
                'hasta'      => $hasta->toIso8601String(),
                'titulo'     => $titulo,
            ]);
        }

        $semanas = $this->dividirEnSemanas($desde, $hasta);

        $kmSemanalPorDispositivo = $this->obtenerKmSemanalPorDispositivo(
            $deviceIds,
            $semanas,
            $titulo,
            $desde,
            $hasta
        );

        $vehiculosExtras = $this->cargarVehiculosExtras($deviceIds);

        $resultado   = [];
        $numSemanas  = \count($semanas) ?: 1;
        $mesLabel    = mb_strtoupper($desde->translatedFormat('F'), 'UTF-8');

        foreach ($deviceIds as $deviceId) {
            $info      = $kmSemanalPorDispositivo[$deviceId] ?? ['nombre_api' => null, 'semanas' => []];
            $nombreApi = $info['nombre_api'];
            $kmSemanas = $info['semanas'];

            $kmTotalMes = array_sum(array_map('floatval', $kmSemanas));
            $kmPromedio = $kmTotalMes / $numSemanas;

            $paramMesTotal = $this->parametrizacionKmService->clasificar('mes_total', $kmTotalMes);
            $paramMesProm  = $this->parametrizacionKmService->clasificar('mes_prom', $kmPromedio);
            $conclusionMes = $paramMesProm;

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

            $extra = $vehiculosExtras[$deviceId] ?? null;

            $resultado[] = [
                'device_id'          => $deviceId,
                'codigo'             => $deviceId,
                'nombre_api'         => $nombreApi,
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
        if ($this->debug) {
            Log::info('[AnalisisRecorrido] (MENSUAL) Iniciando generación', [
                'device_ids' => $deviceIds,
                'desde'      => $desde->toIso8601String(),
                'hasta'      => $hasta->toIso8601String(),
                'titulo'     => $titulo,
            ]);
        }

        $response = $this->ecuatrackerClient->generateKmReport(
            $deviceIds,
            $desde->toDateString(),
            $hasta->toDateString(),
            [
                'title'     => $titulo,
                'from_time' => $desde->format('H:i:s'),
                'to_time'   => $hasta->format('H:i:s'),
            ]
        );

        $kmPorDispositivo = $this->extractKmPorDispositivo($response, $deviceIds);

        $semanas    = $this->dividirEnSemanas($desde, $hasta);
        $numSemanas = \count($semanas) ?: 1;
        $vehiculosExtras = $this->cargarVehiculosExtras($deviceIds);
        $mesLabel  = mb_strtoupper($desde->translatedFormat('F'), 'UTF-8');

        $resultado = [];

        foreach ($deviceIds as $deviceId) {
            $info      = $kmPorDispositivo[$deviceId] ?? ['km_total' => 0.0, 'nombre_api' => null];
            $kmTotal   = (float) ($info['km_total'] ?? 0.0);
            $nombreApi = $info['nombre_api'] ?? null;

            $kmPromedio = $kmTotal / $numSemanas;

            $paramMesTotal = $this->parametrizacionKmService->clasificar('mes_total', $kmTotal);
            $paramMesProm  = $this->parametrizacionKmService->clasificar('mes_prom', $kmPromedio);
            $conclusionMes = $paramMesProm;

            $extra = $vehiculosExtras[$deviceId] ?? null;

            $resultado[] = [
                'device_id'          => $deviceId,
                'codigo'             => $deviceId,
                'nombre_api'         => $nombreApi,
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
     * @return array<int,array{desde:Carbon,hasta:Carbon}>
     */
    protected function dividirEnSemanas(Carbon $desde, Carbon $hasta): array
    {
        $semanas   = [];
        $inicioMes = $desde->copy()->startOfDay();
        $finMes    = $hasta->copy()->endOfDay();

        $dias = (int) ($inicioMes->diffInDays($finMes) + 1);

        if ($dias <= 28) {
            if ($dias <= 7) {
                $numSemanas = 1;
            } elseif ($dias <= 14) {
                $numSemanas = 2;
            } elseif ($dias <= 21) {
                $numSemanas = 3;
            } else {
                $numSemanas = 4;
            }

            $inicio = $inicioMes->copy();

            for ($i = 0; $i < $numSemanas; $i++) {
                $fin = $inicio->copy()->addDays(6)->endOfDay();
                if ($fin->gt($finMes) || $i === $numSemanas - 1) {
                    $fin = $finMes->copy();
                }

                $semanas[] = ['desde' => $inicio, 'hasta' => $fin];

                $inicio = $fin->copy()->addDay()->startOfDay();
                if ($inicio->gt($finMes)) {
                    break;
                }
            }

            return $semanas;
        }

        $numSemanas = 4;
        $baseLength = intdiv($dias, $numSemanas);
        $resto      = $dias % $numSemanas;

        $inicio = $inicioMes->copy();

        for ($i = 0; $i < $numSemanas; $i++) {
            $longitudSemana = $baseLength + ($i < $resto ? 1 : 0);

            $fin = $inicio->copy()->addDays($longitudSemana - 1)->endOfDay();
            if ($fin->gt($finMes)) {
                $fin = $finMes->copy();
            }

            $semanas[] = ['desde' => $inicio, 'hasta' => $fin];

            $inicio = $fin->copy()->addDay()->startOfDay();
            if ($inicio->gt($finMes)) {
                break;
            }
        }

        return $semanas;
    }

    /**
     * @param array<int,int> $deviceIds
     * @param array<int,array{desde:Carbon,hasta:Carbon}> $semanas
     * @return array<int,array{nombre_api:?string,semanas:array<int,float>}>
     */
    protected function obtenerKmSemanalPorDispositivo(
        array $deviceIds,
        array $semanas,
        string $titulo,
        Carbon $desdeOriginal,
        Carbon $hastaOriginal
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

            $responseSemana = $this->ecuatrackerClient->generateKmReport(
                $deviceIds,
                $desdeSemanaDate,
                $hastaSemanaDate,
                [
                    'title'     => "{$titulo} - Semana " . ($idx + 1),
                    'from_time' => $fromTime,
                    'to_time'   => $toTime,
                ]
            );

            $kmSemanaPorDispositivo = $this->extractKmPorDispositivo($responseSemana, $deviceIds);

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
     * @param array<string,mixed> $response
     * @param array<int,int>      $deviceIds
     * @return array<int,array{km_total:float,nombre_api:?string}>
     */
    protected function extractKmPorDispositivo(array $response, array $deviceIds): array
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

        foreach ($deviceIds as $idx => $deviceId) {
            if (!isset($items[$idx]) || !\is_array($items[$idx])) {
                continue;
            }

            $item = $items[$idx];

            $metaDevice = $item['meta']['device.name'] ?? null;
            $nombreApi  = \is_array($metaDevice) ? ($metaDevice['value'] ?? null) : null;

            $distanceStr = $item['totals']['distance']['value'] ?? '0';
            $numericStr  = preg_replace('/[^0-9\.\-]/', '', (string) $distanceStr);
            $distanceKm  = (float) ($numericStr ?: 0);

            $resultado[$deviceId] = [
                'km_total'   => $distanceKm,
                'nombre_api' => $nombreApi,
            ];
        }

        return $resultado;
    }
}
