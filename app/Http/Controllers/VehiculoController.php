<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehiculoStoreRequest;
use App\Http\Requests\VehiculoUpdateRequest;
use App\Http\Resources\VehiculoResource;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class VehiculoController extends Controller
{
    /**
     * Lista de vehículos (con búsqueda y paginación opcionales).
     */
    public function index(Request $request): JsonResponse
    {
        // Búsqueda simple por query param ?search=
        $search = (string) $request->query('search', '');
        $search = trim($search);

        // Paginación: ?per_page=50 (0 => sin paginar)
        $perPage = $request->integer('per_page', 50);
        if ($perPage < 0) {
            $perPage = 50;
        }

        $query = Vehiculo::query()
            ->ordered()
            ->search($search);

        // Si per_page = 0 => sin paginar (para selects grandes, etc.)
        if ($perPage === 0) {
            $vehiculos = $query->get();

            return response()->json(
                VehiculoResource::collection($vehiculos)
            );
        }

        // Límite razonable para no matar el servidor
        $perPage = min($perPage, 200);

        $vehiculos = $query->paginate($perPage);

        // Laravel envuelve con meta de paginación automáticamente
        return response()->json(
            VehiculoResource::collection($vehiculos)
        );
    }

    /**
     * Crea un nuevo vehículo.
     */
    public function store(VehiculoStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $vehiculo = Vehiculo::create($data);

        Log::info('[Vehiculo] Creado', [
            'vehiculo_id' => $vehiculo->id,
            'device_id'   => $vehiculo->device_id,
        ]);

        return response()->json(new VehiculoResource($vehiculo), 201);
    }

    /**
     * Muestra un vehículo.
     */
    public function show(Vehiculo $vehiculo): JsonResponse
    {
        return response()->json(new VehiculoResource($vehiculo));
    }

    /**
     * Actualiza un vehículo.
     */
    public function update(VehiculoUpdateRequest $request, Vehiculo $vehiculo): JsonResponse
    {
        $data = $request->validated();

        $vehiculo->update($data);

        Log::info('[Vehiculo] Actualizado', [
            'vehiculo_id' => $vehiculo->id,
            'device_id'   => $vehiculo->device_id,
        ]);

        return response()->json(new VehiculoResource($vehiculo));
    }

    /**
     * Elimina un vehículo.
     */
    public function destroy(Vehiculo $vehiculo): JsonResponse
    {
        $id       = $vehiculo->id;
        $deviceId = $vehiculo->device_id;

        $vehiculo->delete();

        Log::info('[Vehiculo] Eliminado', [
            'vehiculo_id' => $id,
            'device_id'   => $deviceId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo eliminado correctamente.',
        ]);
    }
}
