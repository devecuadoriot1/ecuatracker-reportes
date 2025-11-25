<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//use App\Http\Controllers\ReporteAnalisisRecorridoController;

// Route::middleware(['web']) //OJO* puedes quitar 'auth' mientras pruebas
//     ->prefix('reportes')
//     ->name('reportes.')
//     ->group(function () {
//         Route::get('analisis-recorrido', [ReporteAnalisisRecorridoController::class, 'create'])
//             ->name('analisis_recorrido.create');

//         Route::post('analisis-recorrido', [ReporteAnalisisRecorridoController::class, 'store'])
//             ->name('analisis_recorrido.store');
//     });
