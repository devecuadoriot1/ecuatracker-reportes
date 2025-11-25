<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AnalisisRecorridoSemanalExport implements FromArray, WithHeadings, ShouldAutoSize
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

    public function headings(): array
    {
        return [
            'Nro.',
            'CODIGO',
            'MARCA',
            'CLASE',
            'MODELO',
            'TIPO',
            'AÑO',
            'PLACAS',
            'AREA ASIGNADA',
            'RESPONSABLE',
            'GERENCIA ASIGNADA',
            'MES',
            // Semana 1
            'SEMANA',
            'KILOMETRAJE RECORRIDO (Km)',
            'PARAMETRIZACIÓN',
            // Semana 2
            'SEMANA',
            'KILOMETRAJE RECORRIDO (Km)',
            'PARAMETRIZACIÓN',
            // Semana 3
            'SEMANA',
            'KILOMETRAJE RECORRIDO (Km)',
            'PARAMETRIZACIÓN',
            // Semana 4
            'SEMANA',
            'KILOMETRAJE RECORRIDO (Km)',
            'PARAMETRIZACIÓN',
            // Resumen mes
            'MES',
            'KILOMETRAJE RECORRIDO (Km)',
            'PARAMETRIZACIÓN',
            'PROMEDIO MES',
            'CONCLUSION MES',
        ];
    }


    public function array(): array
    {
        $rows = [];
        $nro  = 1;

        foreach ($this->data as $item) {
            $semanas = $item['semanas'] ?? [];

            // Preparamos hasta 4 semanas
            $semRow = [];
            for ($i = 0; $i < 4; $i++) {
                $semana = $semanas[$i] ?? null;

                // SEMANA → número (1,2,3,4)
                $semRow[] = $semana['numero']          ?? ($i + 1);
                // KILOMETRAJE RECORRIDO (Km)
                $semRow[] = $semana['km']              ?? null;
                // PARAMETRIZACIÓN
                $semRow[] = $semana['parametrizacion'] ?? null;
            }

            $rows[] = [
                $nro,
                $item['codigo']            ?? $item['device_id'] ?? null,
                $item['marca']             ?? null,
                $item['clase']             ?? null,
                $item['modelo']            ?? null,
                $item['tipo']              ?? null,
                $item['anio']              ?? null,
                $item['placas']            ?? null,
                $item['area_asignada']     ?? null,
                $item['responsable']       ?? null,
                $item['gerencia_asignada'] ?? null,
                // MES (para el bloque semanal)
                $item['mes_label']         ?? null,

                // 4 bloques: SEMANA / KM / PARAM
                ...$semRow,

                // Resumen mensual
                $item['mes_label']         ?? null,              // MES
                $item['km_total_mes']      ?? null,              // KILOMETRAJE RECORRIDO (Km)
                $item['param_mes_total']   ?? null,              // PARAMETRIZACIÓN
                $item['km_promedio_mes']   ?? null,              // PROMEDIO MES
                $item['conclusion_mes']    ?? null,              // CONCLUSION MES
            ];

            $nro++;
        }
        return $rows;
    }
}
