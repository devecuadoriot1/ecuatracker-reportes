<?php

declare(strict_types=1);

namespace App\Services\Ecuatracker;

use App\Models\Vehiculo;
use Illuminate\Support\Facades\Log;

class SyncVehiculosService
{
    public function __construct(
        protected readonly EcuatrackerClient $ecuatrackerClient,
    ) {}

    /**
     * Sincroniza todos los dispositivos desde Ecuatracker a la tabla vehiculos.
     *
     * - Crea registros nuevos si no existen.
     * - Actualiza device_id, nombre_api, codigo, imei si cambian.
     * - No pisa marca/clase/modelo/tipo/anio/area/responsable/gerencia si ya los llenaste a mano.
     */
    public function sync(bool $dryRun = false): array
    {
        $devices = $this->ecuatrackerClient->getDevices();

        $created = 0;
        $updated = 0;

        foreach ($devices as $device) {
            // Estructura típica GPSWOX/Ecuatracker
            $deviceId    = $device['id'] ?? $device['device_id'] ?? null;
            $name        = $device['name'] ?? $device['device_name'] ?? null;
            $imei        = $this->extractImei($device);
            $plateNumber = $this->extractPlateNumber($device);

            if ($deviceId === null || $name === null) {
                Log::warning('[SyncVehiculos] Dispositivo sin id o name', [
                    'raw_device' => $device,
                ]);
                continue;
            }

            $deviceId = (int) $deviceId;
            $codigo   = $this->extractCodigoFromName($name);

            // NUEVO: extraer info de grupo
            $groupInfo = $this->extractGroupInfo($device);
            $groupId    = $groupInfo['group_id'];
            $groupTitle = $groupInfo['group_title'];

            $vehiculo = Vehiculo::where('device_id', $deviceId)->first();

            if ($vehiculo === null) {
                // Crear nuevo
                $vehiculo = new Vehiculo();
                $vehiculo->device_id  = $deviceId;
                $vehiculo->nombre_api = $name;
                $vehiculo->codigo     = $codigo;
                $vehiculo->imei       = $imei;

                if ($plateNumber) {
                    $vehiculo->placas = $plateNumber;
                }

                // grupo siempre viene de la API, no es campo “manual”
                $vehiculo->group_id    = $groupId;
                $vehiculo->group_title = $groupTitle;

                if (! $dryRun) {
                    $vehiculo->save();
                }

                $created++;
                continue;
            }

            // Actualizar solo campos provenientes de la API
            $dirty = false;

            if ($vehiculo->nombre_api !== $name) {
                $vehiculo->nombre_api = $name;
                $dirty = true;
            }

            if ($codigo !== null && $vehiculo->codigo !== $codigo) {
                $vehiculo->codigo = $codigo;
                $dirty = true;
            }

            if ($imei && $vehiculo->imei !== $imei) {
                $vehiculo->imei = $imei;
                $dirty = true;
            }

            if ($plateNumber && $vehiculo->placas !== $plateNumber) {
                $vehiculo->placas = $plateNumber;
                $dirty = true;
            }

            // grupo (siempre viene de la API, se puede pisar seguro)
            if ($vehiculo->group_id !== $groupId) {
                $vehiculo->group_id = $groupId;
                $dirty = true;
            }

            if ($vehiculo->group_title !== $groupTitle) {
                $vehiculo->group_title = $groupTitle;
                $dirty = true;
            }

            if ($dirty && ! $dryRun) {
                $vehiculo->save();
                $updated++;
            }
        }

        Log::info('[SyncVehiculos] Sincronizacion completada', [
            'created' => $created,
            'updated' => $updated,
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }


    /**
     * Obtiene el IMEI desde las llaves habituales de la API.
     *
     * @param  array<string,mixed>  $device
     */
    protected function extractImei(array $device): ?string
    {
        // Prioridad: top-level
        $imei = $device['imei'] ?? $device['device_imei'] ?? null;

        // Fallback: dentro de device_data (según algunos esquemas de Ecuatracker/GPSWOX)
        if (! $imei && isset($device['device_data']) && is_array($device['device_data'])) {
            $imei = $device['device_data']['imei'] ?? $device['device_data']['device_imei'] ?? null;
        }

        // Fallback extra: algunos proveedores usan uniqueid como IMEI
        if (! $imei) {
            $imei = $device['uniqueid'] ?? $device['unique_id'] ?? null;
        }

        return is_string($imei) && $imei !== '' ? $imei : null;
    }

    /**
     * Extrae el codigo interno del name.
     *
     * Ej: "Camioneta-964-PEC9829" => 964
     */
    protected function extractCodigoFromName(string $name): ?int
    {
        $parts = explode('-', $name);

        // Buscamos un segmento que sea numerico
        foreach ($parts as $part) {
            $trim = trim($part);
            if ($trim !== '' && ctype_digit($trim)) {
                return (int) $trim;
            }
        }

        return null;
    }

    /**
     * Obtiene la placa desde las llaves habituales de la API.
     *
     * @param array<string,mixed> $device
     */
    protected function extractPlateNumber(array $device): ?string
    {
        // Prioridad: top-level, luego device_data
        $plate = $device['plate_number'] ?? null;
        if (!$plate && isset($device['device_data']['plate_number'])) {
            $plate = $device['device_data']['plate_number'];
        }

        return is_string($plate) && $plate !== '' ? $plate : null;
    }
    /**
     * Obtiene group_id y group_title desde la estructura del dispositivo.
     *
     * @param  array<string,mixed>  $device
     * @return array{group_id:?int,group_title:?string}
     */
    protected function extractGroupInfo(array $device): array
    {
        // Primero intentamos con las llaves que ya ponemos en flattenDevices()
        $groupId    = $device['group_id']    ?? null;
        $groupTitle = $device['group_title'] ?? null;

        // Fallback: por si algún día el JSON cambia y sólo viene en device_data
        $deviceData = $device['device_data'] ?? [];
        if ($groupId === null && is_array($deviceData) && isset($deviceData['group_id'])) {
            $groupId = $deviceData['group_id'];
        }

        // Normalizamos tipos
        $groupId = is_numeric($groupId) ? (int) $groupId : null;
        $groupTitle = is_string($groupTitle) && $groupTitle !== '' ? $groupTitle : null;

        return [
            'group_id'    => $groupId,
            'group_title' => $groupTitle,
        ];
    }
}
