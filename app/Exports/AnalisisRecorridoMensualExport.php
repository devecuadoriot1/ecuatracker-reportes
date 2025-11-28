<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class AnalisisRecorridoMensualExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    /**
     * @var array<int, array<string,mixed>>
     */
    protected array $data;

    /**
     * @param array<int, array<string,mixed>> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Título de la pestaña de la hoja de Excel.
     */
    public function title(): string
    {
        return 'Análisis de Recorrido de Kilometraje';
    }

    public function headings(): array
    {
        return [
            'Device ID',
            'Código',
            'Dispositivo',
            'Marca',
            'Clase',
            'Modelo',
            'Tipo',
            'Año',
            'Placas',
            'Área asignada',
            'Responsable',
            'Gerencia asignada',
            'Mes (label)',
            'Km total mes',
            'Param. km total mes',
            'Km promedio mes',
            'Param. km promedio mes',
            'Conclusión mes',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->data as $item) {
            $rows[] = [
                $item['device_id']         ?? null,
                $item['codigo']            ?? null,
                $item['nombre_api']        ?? null,
                $item['marca']             ?? null,
                $item['clase']             ?? null,
                $item['modelo']            ?? null,
                $item['tipo']              ?? null,
                $item['anio']              ?? null,
                $item['placas']            ?? null,
                $item['area_asignada']     ?? null,
                $item['responsable']       ?? null,
                $item['gerencia_asignada'] ?? null,
                $item['mes_label']         ?? null,
                $item['km_total_mes']      ?? null,
                $item['param_mes_total']   ?? null,
                $item['km_promedio_mes']   ?? null,
                $item['param_mes_promedio'] ?? null,
                $item['conclusion_mes']    ?? null,
            ];
        }

        return $rows;
    }
}
