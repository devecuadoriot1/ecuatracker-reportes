<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'device_id',
        'codigo',
        'imei',
        'nombre_api',
        'marca',
        'clase',
        'modelo',
        'tipo',
        'anio',
        'placas',
        'area_asignada',
        'responsable',
        'gerencia_asignada',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'codigo'    => 'integer',
        'anio'      => 'integer',
    ];

    /**
     * Scope de búsqueda reutilizable por nombre_api, placas, marca, modelo.
     *
     * @param  Builder      $query
     * @param  string|null  $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = $search !== null ? trim($search) : '';

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('nombre_api', 'like', '%' . $search . '%')
                ->orWhere('placas', 'like', '%' . $search . '%')
                ->orWhere('marca', 'like', '%' . $search . '%')
                ->orWhere('modelo', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope de orden por defecto (reutilizable en otros endpoints).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('nombre_api')
            ->orderBy('placas');
    }
}
