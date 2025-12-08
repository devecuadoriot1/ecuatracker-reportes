<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ParametrizacionKm extends Model
{
    use HasFactory;
    public const TIPO_SEMANA    = 'semana';
    public const TIPO_MES_TOTAL = 'mes_total';
    public const TIPO_MES_PROM  = 'mes_prom';

    protected $table = 'parametrizaciones_km';

    protected $fillable = [
        'tipo',
        'nombre',
        'km_min',
        'km_max',
        'orden',
    ];

    protected $casts = [
        'km_min' => 'float',
        'km_max' => 'float',
        'orden'  => 'integer',
    ];
    /**
     * Scope para filtrar por tipo.
     */
    public function scopeTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }
}
