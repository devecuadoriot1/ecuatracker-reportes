<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteAnalisisRecorridoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('reportes/analisis-recorrido', [ReporteAnalisisRecorridoController::class, 'create'])
    ->name('reportes.analisis_recorrido.create');

Route::post('reportes/analisis-recorrido', [ReporteAnalisisRecorridoController::class, 'store'])
    ->name('reportes.analisis_recorrido.store');
