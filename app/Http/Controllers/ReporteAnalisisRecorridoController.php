<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\GenerarAnalisisRecorridoRequest;
use App\Services\Ecuatracker\EcuatrackerClient;
use App\Services\Reportes\AnalisisRecorridoService;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnalisisRecorridoSemanalExport;
use App\Exports\AnalisisRecorridoMensualExport;

class ReporteAnalisisRecorridoController extends Controller
{
    public function __construct(
        protected readonly EcuatrackerClient $ecuatrackerClient,
        protected readonly AnalisisRecorridoService $analisisRecorridoService,
    ) {
        // $this->middleware('auth'); // cuando actives auth/roles
    }

    /**
     * Carga datos base para el formulario de análisis de recorrido.
     */

    public function create(Request $request)
    {
        $vehiculos = Vehiculo::orderBy('nombre_api')
            ->orderBy('placas')
            ->get();

        return view('reportes.analisis_recorrido.create', [
            'vehiculos' => $vehiculos,
        ]);
    }
    // public function create(Request $request): RedirectResponse|\Illuminate\View\View
    // {
    //     try {
    //         $devices = $this->ecuatrackerClient->getDevices();
    //         $groups  = $this->ecuatrackerClient->getDeviceGroups();
    //     } catch (ApiException $e) {
    //         Log::error('Error cargando devices/groups para formulario de reporte', [
    //             'error'   => $e->getMessage(),
    //             'context' => $e->getContext(),
    //         ]);

    //         return back()->withErrors([
    //             'api' => 'No se pudo obtener la lista de dispositivos desde Ecuatracker. Intente nuevamente.',
    //         ]);
    //     }

    //     return view('reportes.analisis_recorrido.create', [
    //         'devices' => $devices,
    //         'groups'  => $groups,
    //     ]);
    // }

    /**
     * Procesa la solicitud y genera el reporte (JSON/Excel/PDF).
     */
    public function store(GenerarAnalisisRecorridoRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $data = $request->validated();

        // Construcción segura de fechas + horas
        $horaDesde = $data['hora_desde'] ?? '00:00';
        $horaHasta = $data['hora_hasta'] ?? '23:59';

        $desde = Carbon::parse($data['fecha_desde'] . ' ' . $horaDesde);
        $hasta = Carbon::parse($data['fecha_hasta'] . ' ' . $horaHasta);

        if ($hasta->lt($desde)) {
            return back()
                ->withInput()
                ->withErrors([
                    'rango' => 'La fecha y hora de fin debe ser mayor o igual a la fecha y hora de inicio.',
                ]);
        }

        // Convertimos vehiculo_ids → device_ids
        $vehiculoIds = array_map('intval', $data['vehiculo_ids']);

        $deviceIds = Vehiculo::whereIn('id', $vehiculoIds)
            ->pluck('device_id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $modo    = $data['modo'];
        $formato = $data['formato'];

        try {
            //OJO* Seguridad: aquí en el futuro puedes validar que los device_ids
            // realmente pertenecen a la organización / usuario autenticado.

            if ($modo === 'semanal') {
                $resultado = $this->analisisRecorridoService->generar(
                    $deviceIds,
                    $desde,
                    $hasta,
                    $data['titulo']
                );
            } else {
                $resultado = $this->analisisRecorridoService->generarMensual(
                    $deviceIds,
                    $desde,
                    $hasta,
                    $data['titulo']
                );
            }
        } catch (ApiException $e) {
            Log::error('Error al generar reporte de análisis de recorrido (API)', [
                'error'   => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'api' => 'No se pudo obtener datos del proveedor de rastreo. Intente nuevamente más tarde.',
                ]);
        } catch (\Throwable $e) {
            Log::error('Error inesperado al generar reporte de análisis de recorrido', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Ocurrió un error inesperado al generar el reporte.',
                ]);
        }

        // Excel
        if ($formato === 'excel') {
            $fileName = sprintf(
                'reporte_analisis_recorrido_%s_%s_%s.xlsx',
                $modo,
                $desde->format('Ymd_His'),
                $hasta->format('Ymd_His')
            );

            $export = $modo === 'semanal'
                ? new AnalisisRecorridoSemanalExport($resultado)
                : new AnalisisRecorridoMensualExport($resultado);

            return Excel::download($export, $fileName);
        }

        // PDF (por ahora solo JSON de prueba)
        if ($formato === 'pdf') {
            return response()->json([
                'success'   => true,
                'formato'   => 'pdf',
                'modo'      => $modo,
                'titulo'    => $data['titulo'],
                'desde'     => $desde->toIso8601String(),
                'hasta'     => $hasta->toIso8601String(),
                'resultado' => $resultado,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Formato de salida no soportado.',
        ], 422);
    }
}
