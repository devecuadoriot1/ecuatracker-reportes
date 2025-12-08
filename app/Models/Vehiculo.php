<?php

declare(strict_types=1);

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
        'group_id',
        'group_title',
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
        'group_id'  => 'integer',
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
        $search = trim((string) $search);

        return $query->when($search !== '', function (Builder $q) use ($search) {
            $q->where(function (Builder $q2) use ($search) {
                $q2->where('nombre_api', 'like', "%{$search}%")
                    ->orWhere('placas', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%");
            });
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

    /**
     * Etiqueta amigable para selects / listados.
     */
    public function getSelectLabelAttribute(): string
    {
        $parts = [];

        if ($this->nombre_api) {
            $parts[] = $this->nombre_api;
        }

        if ($this->placas) {
            $parts[] = 'Placa: ' . $this->placas;
        }

        $marcaModelo = trim(($this->marca ?? '') . ' ' . ($this->modelo ?? ''));
        if ($marcaModelo !== '') {
            $parts[] = $marcaModelo;
        }

        if ($this->area_asignada) {
            $parts[] = 'Área: ' . $this->area_asignada;
        }

        return $parts ? implode(' · ', $parts) : 'Vehículo #' . $this->id;
    }
}
