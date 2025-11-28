<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehiculoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Luego aquí se puede reemplazar por autorización con policies/roles
        return true;
    }

    // Reglas de validación para la creación de vehículos
    public function rules(): array
    {
        return [
            'device_id'         => ['required', 'integer', 'unique:vehiculos,device_id'],
            'codigo'            => ['nullable', 'integer'],
            'imei'              => ['nullable', 'string', 'max:50', 'unique:vehiculos,imei'],
            'nombre_api'        => ['nullable', 'string', 'max:255'],
            'marca'             => ['nullable', 'string', 'max:100'],
            'clase'             => ['nullable', 'string', 'max:100'],
            'modelo'            => ['nullable', 'string', 'max:100'],
            'tipo'              => ['nullable', 'string', 'max:100'],
            'anio'              => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'placas'            => ['nullable', 'string', 'max:50'],
            'area_asignada'     => ['nullable', 'string', 'max:150'],
            'responsable'       => ['nullable', 'string', 'max:150'],
            'gerencia_asignada' => ['nullable', 'string', 'max:150'],
        ];
    }
}
