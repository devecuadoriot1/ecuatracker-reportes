<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReporteAnalisisRecorridoController;
use App\Http\Controllers\VehiculoController;
use PhpOffice\PhpSpreadsheet\Shared\OLE\PPS\Root;

Route::post('test/analisis-recorrido', [ReporteAnalisisRecorridoController::class, 'store']);
Route::apiResource('vehiculos', VehiculoController::class)->names([
    'index' => 'vehiculos.index',
    'store' => 'vehiculos.store',
    'show' => 'vehiculos.show',
    'update' => 'vehiculos.update',
    'destroy' => 'vehiculos.destroy',
]);
