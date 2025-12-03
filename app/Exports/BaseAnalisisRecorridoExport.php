<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/* Base génerica para export de Analsis de Recorrido*/

abstract class BaseAnalisisRecorridoExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    /** 
     * @var array<int, array<string,mixed>>
     */
    protected array $data;

    /** 
     * @param array<int, array,<string,mixed>> $data
     */

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /** 
     * Titulo de la pestaña de la hoja
     */
    public function title(): string
    {
        return 'Análisis de Recorrido de Kilometraje';
    }
}
