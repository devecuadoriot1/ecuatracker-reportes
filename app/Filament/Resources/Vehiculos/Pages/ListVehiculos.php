<?php

namespace App\Filament\Resources\Vehiculos\Pages;

use App\Filament\Resources\Vehiculos\VehiculoResource;
use App\Imports\VehiculosDatosExtraImport;
use App\Services\Vehiculos\ImportDatosVehiculosService;
use App\Services\Ecuatracker\SyncVehiculosService;
use App\Exceptions\ApiException;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ListVehiculos extends ListRecords
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // Acción para importar datos extra desde Excel (ya la teníamos)
            Action::make('importarDatosExcel')
                ->label('Importar datos desde Excel')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('secondary')
                ->modalHeading('Importar datos de vehículos')
                ->modalSubheading('Sube un archivo Excel con la columna COD y los datos extra de cada vehículo.')
                ->form([
                    Forms\Components\FileUpload::make('archivo')
                        ->label('Archivo Excel')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'application/vnd.ms-excel', // .xls
                        ])
                        ->maxSize(10 * 1024)
                        ->storeFiles(false),
                ])
                ->action(function (array $data, ImportDatosVehiculosService $service) {
                    $file = $data['archivo'];

                    try {
                        $import = new VehiculosDatosExtraImport();
                        Excel::import($import, $file);

                        $rows   = $import->getRows();
                        $result = $service->importar($rows);

                        $body = sprintf(
                            'Filas leídas: %d | Actualizados: %d | Sin código: %d | No encontrados: %d | Errores: %d',
                            $result['total_rows'],
                            $result['actualizados'],
                            $result['sin_codigo'],
                            $result['no_encontrados'],
                            $result['errores'],
                        );

                        Notification::make()
                            ->title('Importación completada')
                            ->body($body)
                            ->success()
                            ->send();

                        if ($result['errores'] > 0) {
                            Log::warning('Importación de vehículos finalizada con errores', $result);
                        }
                    } catch (\Throwable $e) {
                        Log::error('Error crítico al importar datos de vehículos desde Excel', [
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Error al importar Excel')
                            ->body('Ocurrió un error al procesar el archivo. Verifique el formato e intente nuevamente.')
                            ->danger()
                            ->send();
                    }
                }),

            // NUEVA ACCIÓN: sincronizar desde Ecuatracker
            Action::make('syncDesdeEcuatracker')
                ->label('Sincronizar desde Ecuatracker')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sincronizar vehículos desde Ecuatracker')
                ->modalSubheading('Esto consultará ecuatracker.com y creará/actualizará vehículos según la información actual de la API.')
                ->action(function (SyncVehiculosService $syncVehiculosService) {
                    try {
                        $result = $syncVehiculosService->sync(false);

                        $body = sprintf(
                            'Total dispositivos: %d | Creados: %d | Actualizados: %d | Sin cambios: %d',
                            $result['total']   ?? 0,
                            $result['created'] ?? 0,
                            $result['updated'] ?? 0,
                            $result['skipped'] ?? 0,
                        );

                        Notification::make()
                            ->title('Sincronización completada')
                            ->body($body)
                            ->success()
                            ->send();

                        // refrescamos la tabla para ver los nuevos registros
                        $this->resetTable(); // método de ListRecords
                    } catch (ApiException $e) {
                        Log::warning('Error de API al sincronizar vehículos desde Ecuatracker', [
                            'error' => $e->getMessage(),
                            'context' => $e->getContext() ?? [],
                        ]);

                        Notification::make()
                            ->title('Error al comunicarse con Ecuatracker')
                            ->body('La API de Ecuatracker devolvió un error. Revisa los logs para más detalle.')
                            ->danger()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::error('Error inesperado al sincronizar vehículos desde Ecuatracker', [
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Error al sincronizar vehículos')
                            ->body('Ocurrió un error inesperado durante la sincronización. Intente nuevamente más tarde.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
