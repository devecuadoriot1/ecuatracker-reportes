<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehiculoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehiculoId = $this->route('vehiculo')?->id ?? null;

        return [
            'device_id'        => [
                'required',
                'integer',
                Rule::unique('vehiculos', 'device_id')->ignore($vehiculoId),
            ],
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
