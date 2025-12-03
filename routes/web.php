<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteAnalisisRecorridoController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

Route::middleware(['web', FilamentAuthenticate::class])->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('analisis-recorrido/download', [ReporteAnalisisRecorridoController::class, 'download'])
        ->name('analisis-recorrido.download');
});


// Route::post('reportes/analisis-recorrido', [ReporteAnalisisRecorridoController::class, 'store'])
//     ->name('reportes.analisis_recorrido.store');