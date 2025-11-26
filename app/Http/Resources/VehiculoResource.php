<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Vehiculo $this */
        return [
            'id'                => $this->id,
            'device_id'         => $this->device_id,
            'nombre_api'        => $this->nombre_api,
            'marca'             => $this->marca,
            'clase'             => $this->clase,
            'modelo'            => $this->modelo,
            'tipo'              => $this->tipo,
            'anio'              => $this->anio,
            'placas'            => $this->placas,
            'area_asignada'     => $this->area_asignada,
            'responsable'       => $this->responsable,
            'gerencia_asignada' => $this->gerencia_asignada,

            //FRONT* ÚTIL PARA EL FRONT: label listo para un <select>
            'label'             => $this->nombre_api
                ?? trim($this->placas . ' ' . $this->marca . ' ' . $this->modelo),

            // valor "recomendado" para selects (id interno, no el device_id)
            'value'             => $this->id,
        ];
    }
}
