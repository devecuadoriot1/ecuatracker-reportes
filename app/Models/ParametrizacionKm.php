<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametrizacionKm extends Model
{
    use HasFactory;

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
}
