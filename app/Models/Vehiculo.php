<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'device_id',
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
        'anio' => 'integer',
    ];
}
