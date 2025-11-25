<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReporteAnalisisRecorridoController;

Route::post('test/analisis-recorrido', [ReporteAnalisisRecorridoController::class, 'store']);
