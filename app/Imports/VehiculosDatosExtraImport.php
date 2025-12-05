<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

/**
 * Import sencillo que solo carga las filas en memoria,
 * con encabezados normalizados en formato slug:
 *   "GERENCIA GENERAL"  -> "gerencia_general"
 *   "COD"               -> "cod"
 *   "AÑO"               -> "ano"
 *   "AREA"              -> "area"
 */
class VehiculosDatosExtraImport implements ToCollection, WithHeadingRow
{
    /**
     * @var Collection<int, array<string, mixed>>
     */
    protected Collection $rows;

    public function __construct()
    {
        // Aseguramos que los encabezados se formateen como slug.
        HeadingRowFormatter::default('slug');
        $this->rows = collect();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRows(): Collection
    {
        return $this->rows;
    }
}
