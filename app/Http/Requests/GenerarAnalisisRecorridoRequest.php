<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class GenerarAnalisisRecorridoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Por ahora permitimos siempre. Más adelante puedes usar policies o gates:
        // return $this->user()?->can('generar-analisis-recorrido') ?? false;
        return true;
    }

    /**
     * Normaliza y limpia los datos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'titulo'      => trim((string) $this->input('titulo', '')),
            'modo'        => strtolower((string) $this->input('modo', 'semanal')),
            'formato'     => strtolower((string) $this->input('formato', 'excel')),
            // Si el front no envía horas, usamos los valores por defecto
            'hora_desde'  => $this->input('hora_desde') ?: '00:00',
            'hora_hasta'  => $this->input('hora_hasta') ?: '23:59',
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo'       => ['bail', 'required', 'string', 'max:255'],

            'fecha_desde'  => ['bail', 'required', 'date'],
            'fecha_hasta'  => ['bail', 'required', 'date', 'after_or_equal:fecha_desde'],

            'hora_desde'   => ['required', 'date_format:H:i'],
            'hora_hasta'   => ['required', 'date_format:H:i'],

            'vehiculo_ids'   => ['bail', 'required', 'array', 'min:1'],
            'vehiculo_ids.*' => ['integer', 'min:1', 'distinct', 'exists:vehiculos,id'],

            'formato'      => ['required', Rule::in(['excel', 'pdf'])],
            'modo'         => ['required', Rule::in(['semanal', 'mensual'])],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Debe ingresar un título para el reporte.',
            'titulo.max'      => 'El título no puede exceder los 255 caracteres.',

            'fecha_desde.required' => 'Debe indicar la fecha de inicio.',
            'fecha_desde.date'     => 'La fecha de inicio no tiene un formato válido.',

            'fecha_hasta.required'      => 'Debe indicar la fecha de fin.',
            'fecha_hasta.date'          => 'La fecha de fin no tiene un formato válido.',
            'fecha_hasta.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

            'hora_desde.required'    => 'Debe indicar la hora de inicio.',
            'hora_desde.date_format' => 'La hora de inicio debe tener el formato HH:MM (por ejemplo, 08:00).',

            'hora_hasta.required'    => 'Debe indicar la hora de fin.',
            'hora_hasta.date_format' => 'La hora de fin debe tener el formato HH:MM (por ejemplo, 17:30).',

            'vehiculo_ids.required' => 'Debe seleccionar al menos un vehículo.',
            'vehiculo_ids.array'    => 'El formato de vehículos seleccionados no es válido.',
            'vehiculo_ids.min'      => 'Debe seleccionar al menos un vehículo.',
            'vehiculo_ids.*.integer' => 'Cada vehículo seleccionado debe ser un identificador válido.',
            'vehiculo_ids.*.min'    => 'Cada vehículo seleccionado debe ser un identificador válido.',
            'vehiculo_ids.*.distinct' => 'Hay vehículos duplicados en la selección.',
            'vehiculo_ids.*.exists'   => 'Alguno de los vehículos seleccionados no existe.',

            'formato.required' => 'Debe indicar el formato del reporte.',
            'formato.in'       => 'El formato de salida debe ser "excel" o "pdf".',

            'modo.required' => 'Debe indicar el modo del reporte (semanal o mensual).',
            'modo.in'       => 'El modo de reporte debe ser "semanal" o "mensual".',
        ];
    }

    /**
     * Alias legibles para los nombres de los campos.
     */
    public function attributes(): array
    {
        return [
            'fecha_desde'  => 'fecha de inicio',
            'fecha_hasta'  => 'fecha de fin',
            'hora_desde'   => 'hora de inicio',
            'hora_hasta'   => 'hora de fin',
            'vehiculo_ids' => 'vehículos',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $fechaDesde = $this->input('fecha_desde');
            $fechaHasta = $this->input('fecha_hasta');

            if (! $fechaDesde || ! $fechaHasta) {
                return;
            }

            try {
                $desde = Carbon::parse($fechaDesde . ' ' . $this->input('hora_desde', '00:00'));
                $hasta = Carbon::parse($fechaHasta . ' ' . $this->input('hora_hasta', '23:59'));

                if ($desde->diffInDays($hasta) > 31) {
                    $validator->errors()->add(
                        'fecha_hasta',
                        'El rango máximo permitido para este reporte es de 31 días.'
                    );
                }
            } catch (\Throwable $e) {
                // Las reglas 'date' y 'date_format' ya se encargan de marcar errores de formato.
            }
        });
    }
}
