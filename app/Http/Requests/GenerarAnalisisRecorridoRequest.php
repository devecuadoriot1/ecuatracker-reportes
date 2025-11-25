<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerarAnalisisRecorridoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // aquí luego puedes validar permisos/roles
    }

    public function rules(): array
    {
        return [
            'titulo'       => ['required', 'string', 'max:255'],
            'fecha_desde'  => ['required', 'date'],
            'fecha_hasta'  => ['required', 'date', 'after_or_equal:fecha_desde'],
            'hora_desde'   => ['nullable', 'date_format:H:i'],
            'hora_hasta'   => ['nullable', 'date_format:H:i'],

            'device_ids'   => ['required', 'array', 'min:1'],
            'device_ids.*' => ['integer'],

            'formato'      => ['required', 'in:excel,pdf'],
            'modo'         => ['required', 'in:semanal,mensual'],
        ];
    }


    public function messages(): array
    {
        return [
            'modo.required' => 'Debe indicar el modo del reporte (semanal o mensual).',
            'modo.in'       => 'El modo de reporte debe ser "semanal" o "mensual".',
        ];
    }
}
