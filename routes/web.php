<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteAnalisisRecorridoController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

Route::redirect('/', '/admin/login');

Route::middleware(['web', FilamentAuthenticate::class])->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('analisis-recorrido/download', [ReporteAnalisisRecorridoController::class, 'download'])
        ->name('analisis-recorrido.download');
});
