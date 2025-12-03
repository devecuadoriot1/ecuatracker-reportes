<?php

namespace App\Exports;


class AnalisisRecorridoMensualExport extends BaseAnalisisRecorridoExport
{
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
            'Mes',
            'Km total mes',
            'Param. km total mes',
            'Km promedio mes',
            //OJO'Param. km promedio mes',
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
                //OJO $item['param_mes_promedio'] ?? null,
                $item['conclusion_mes']    ?? null,
            ];
        }

        return $rows;
    }
}
