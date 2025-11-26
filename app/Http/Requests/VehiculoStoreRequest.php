<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehiculoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    //Reglas de validación para la creación/actualización de vehículos
    public function rules(): array
    {
        return [
            'device_id'        => ['required', 'integer', 'unique:vehiculos,device_id'],
            'nombre_api'       => ['nullable', 'string', 'max:255'],
            'marca'            => ['nullable', 'string', 'max:100'],
            'clase'            => ['nullable', 'string', 'max:100'],
            'modelo'           => ['nullable', 'string', 'max:100'],
            'tipo'             => ['nullable', 'string', 'max:100'],
            'anio'             => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'placas'           => ['nullable', 'string', 'max:50'],
            'area_asignada'    => ['nullable', 'string', 'max:150'],
            'responsable'      => ['nullable', 'string', 'max:150'],
            'gerencia_asignada' => ['nullable', 'string', 'max:150'],
        ];
    }
}
