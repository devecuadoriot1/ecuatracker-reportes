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
            // Estructura tipica GPSWOX/Ecuatracker
            $deviceId = $device['id'] ?? $device['device_id'] ?? null;
            $name        = $device['name'] ?? $device['device_name'] ?? null;
            $imei        = $device['imei'] ?? $device['device_imei'] ?? null;
            $plateNumber = $this->extractPlateNumber($device);

            if ($deviceId === null || $name === null) {
                Log::warning('[SyncVehiculos] Dispositivo sin id o name', [
                    'raw_device' => $device,
                ]);
                continue;
            }

            $deviceId = (int) $deviceId;
            $codigo   = $this->extractCodigoFromName($name);

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

                if (!$dryRun) {
                    $vehiculo->save();
                }

                $created++;
                continue;
            }

            // Actualizar solo campos provenientes de la API (no tocamos marca/clase/etc.)
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

            // Actualizar placas si vienen en la API y cambiaron
            if ($plateNumber && $vehiculo->placas !== $plateNumber) {
                $vehiculo->placas = $plateNumber;
                $dirty = true;
            }

            if ($dirty && !$dryRun) {
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
}
