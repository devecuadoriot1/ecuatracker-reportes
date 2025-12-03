<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exports\AnalisisRecorridoMensualExport;
use App\Exports\AnalisisRecorridoSemanalExport;
use App\Http\Requests\GenerarAnalisisRecorridoRequest;
use App\Models\Vehiculo;
use App\Services\Ecuatracker\EcuatrackerClient;
use App\Services\Reportes\AnalisisRecorridoService;
use App\Support\SanitizesPdfContent;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReporteAnalisisRecorridoController extends Controller
{
    use SanitizesPdfContent;

    public function __construct(
        protected readonly EcuatrackerClient $ecuatrackerClient,
        protected readonly AnalisisRecorridoService $analisisRecorridoService,
    ) {
        // $this->middleware('auth'); // cuando actives auth/roles
    }

    /**
     * Antes cargaba la vista Blade del formulario.
     * Ahora redirige al panel de Filament.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('filament.admin.pages.analisis-recorrido');
    }

    /**
     * Procesa la solicitud vía POST (podrías usarlo como API interna).
     */
    public function store(GenerarAnalisisRecorridoRequest $request): Response
    {
        $data = $request->validated();

        return $this->generateReportResponse($data, true);
    }

    /**
     * Descarga el reporte vía GET (usado desde Filament).
     */
    public function download(Request $request): Response
    {
        // Normalizamos los parámetros de query a la estructura esperada.
        $data = [
            'titulo'       => (string) $request->query('titulo', ''),
            'modo'         => (string) $request->query('modo', 'semanal'),
            'formato'      => (string) $request->query('formato', 'excel'),
            'fecha_desde'  => (string) $request->query('fecha_desde', ''),
            'fecha_hasta'  => (string) $request->query('fecha_hasta', ''),
            'hora_desde'   => (string) $request->query('hora_desde', '00:00'),
            'hora_hasta'   => (string) $request->query('hora_hasta', '23:59'),
            'vehiculo_ids' => (array) $request->query('vehiculo_ids', []),
        ];

        return $this->generateReportResponse($data, false);
    }

    /**
     * Lógica común para generar y devolver el reporte.
     *
     * @param  array<string,mixed>  $data
     */
    private function generateReportResponse(array $data, bool $fromPostRequest): Response
    {
        // ---------------- Fechas y horas ----------------
        try {
            $horaDesde = $data['hora_desde'] ?? '00:00';
            $horaHasta = $data['hora_hasta'] ?? '23:59';

            $desde = Carbon::parse(($data['fecha_desde'] ?? '') . ' ' . $horaDesde);
            $hasta = Carbon::parse(($data['fecha_hasta'] ?? '') . ' ' . $horaHasta);
        } catch (\Throwable $e) {
            Log::warning('Rango de fechas inválido en análisis de recorrido', [
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);

            $msg = 'El rango de fechas/horas es inválido.';

            if ($fromPostRequest) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['rango' => $msg]);
            }

            abort(422, $msg);
        }

        if ($hasta->lt($desde)) {
            $msg = 'La fecha y hora de fin debe ser mayor o igual a la fecha y hora de inicio.';

            if ($fromPostRequest) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['rango' => $msg]);
            }

            abort(422, $msg);
        }

        // ---------------- Vehículos / dispositivos ----------------
        $vehiculoIds = array_map('intval', $data['vehiculo_ids'] ?? []);

        $deviceIds = Vehiculo::whereIn('id', $vehiculoIds)
            ->pluck('device_id')
            ->filter() // por si algún vehículo no tiene device_id
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        if (empty($deviceIds)) {
            $msg = 'Debe seleccionar al menos un vehículo con dispositivo asignado.';

            if ($fromPostRequest) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['vehiculo_ids' => $msg]);
            }

            abort(422, $msg);
        }

        $modo    = $data['modo'] ?? 'semanal';
        $formato = $data['formato'] ?? 'excel';
        $titulo  = $this->sanitizeStringForPdf($data['titulo'] ?? '');

        // ---------------- Llamada al servicio ----------------
        try {
            if ($modo === 'semanal') {
                $resultado = $this->analisisRecorridoService->generar(
                    $deviceIds,
                    $desde,
                    $hasta,
                    $titulo,
                );
            } else {
                $resultado = $this->analisisRecorridoService->generarMensual(
                    $deviceIds,
                    $desde,
                    $hasta,
                    $titulo,
                );
            }
        } catch (ApiException $e) {
            Log::error('Error al generar reporte de análisis de recorrido (API)', [
                'error'   => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            $msg = 'No se pudo obtener datos del proveedor de rastreo. Intente nuevamente más tarde.';

            if ($fromPostRequest) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['api' => $msg]);
            }

            abort(502, $msg);
        } catch (\Throwable $e) {
            Log::error('Error inesperado al generar reporte de análisis de recorrido', [
                'error' => $e->getMessage(),
            ]);

            $msg = 'Ocurrió un error inesperado al generar el reporte.';

            if ($fromPostRequest) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['general' => $msg]);
            }

            abort(500, $msg);
        }

        // ---------------- Excel ----------------
        if ($formato === 'excel') {
            $fileName = sprintf(
                'reporte_analisis_recorrido_%s_%s_%s.xlsx',
                $modo,
                $desde->format('Ymd_His'),
                $hasta->format('Ymd_His'),
            );

            $export = $modo === 'semanal'
                ? new AnalisisRecorridoSemanalExport($resultado)
                : new AnalisisRecorridoMensualExport($resultado);

            Log::info('Descargando reporte de análisis de recorrido en Excel', [
                'modo'   => $modo,
                'desde'  => $desde->toIso8601String(),
                'hasta'  => $hasta->toIso8601String(),
                'titulo' => $titulo,
            ]);

            return Excel::download($export, $fileName);
        }

        // ---------------- PDF ----------------
        if ($formato === 'pdf') {
            $fileName = sprintf(
                'reporte_analisis_recorrido_%s_%s_%s.pdf',
                $modo,
                $desde->format('Ymd_His'),
                $hasta->format('Ymd_His'),
            );

            $view = $modo === 'semanal'
                ? 'reportes.analisis_recorrido.pdf_semanal'
                : 'reportes.analisis_recorrido.pdf_mensual';

            $resultadoSanitizado = $this->sanitizeArrayForPdf($resultado);

            $html = view($view, [
                'modo'      => $modo,
                'titulo'    => $titulo,
                'desde'     => $desde,
                'hasta'     => $hasta,
                'resultado' => $resultadoSanitizado,
            ])->render();

            $html = $this->sanitizeHtmlForPdf($html);

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

            Log::info('Descargando reporte de análisis de recorrido en PDF', [
                'modo'   => $modo,
                'desde'  => $desde->toIso8601String(),
                'hasta'  => $hasta->toIso8601String(),
                'titulo' => $titulo,
            ]);

            return $pdf->download($fileName);
        }

        // ---------------- Formato no soportado (fallback JSON) ----------------
        return new JsonResponse([
            'success' => false,
            'message' => 'Formato de salida no soportado.',
        ], 422);
    }
}
