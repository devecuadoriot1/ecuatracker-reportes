<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Vehiculo;
use App\Services\Reportes\AnalisisRecorridoService; // lo puedes dejar por si luego haces previews
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class AnalisisRecorrido extends Page
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon   = 'heroicon-o-chart-bar';
    protected static string|UnitEnum|null   $navigationGroup  = 'Reportes';
    protected static ?string                $navigationLabel  = 'Análisis de recorrido';
    protected static ?int                   $navigationSort   = 20;

    protected string $view = 'filament.pages.analisis-recorrido';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'modo'    => 'semanal',
            'formato' => 'excel',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título del reporte')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('modo')
                    ->label('Modo')
                    ->options([
                        'semanal' => 'Semanal + resumen mensual',
                        'mensual' => 'Mensual (solo totales)',
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('desde')
                    ->label('Desde')
                    ->required(),

                Forms\Components\DatePicker::make('hasta')
                    ->label('Hasta')
                    ->required(),

                Forms\Components\TimePicker::make('hora_desde')
                    ->label('Hora desde')
                    ->seconds(false)
                    ->nullable(),

                Forms\Components\TimePicker::make('hora_hasta')
                    ->label('Hora hasta')
                    ->seconds(false)
                    ->nullable(),

                Forms\Components\Select::make('formato')
                    ->label('Formato')
                    ->options([
                        'excel' => 'Excel',
                        'pdf'   => 'PDF',
                    ])
                    ->required(),

                Forms\Components\Select::make('vehiculo_ids')
                    ->label('Vehículos')
                    ->multiple()
                    ->required()
                    ->options(
                        Vehiculo::ordered()
                            ->get()
                            ->mapWithKeys(function (Vehiculo $vehiculo): array {
                                $parts = [];

                                if ($vehiculo->nombre_api) {
                                    $parts[] = $vehiculo->nombre_api;
                                }

                                if ($vehiculo->placas) {
                                    $parts[] = 'Placa: ' . $vehiculo->placas;
                                }

                                $marcaModelo = trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? ''));

                                if ($marcaModelo !== '') {
                                    $parts[] = $marcaModelo;
                                }

                                if ($vehiculo->area_asignada) {
                                    $parts[] = 'Área: ' . $vehiculo->area_asignada;
                                }

                                $label = $parts ? implode(' · ', $parts) : 'Vehículo #' . $vehiculo->id;

                                return [$vehiculo->id => $label];
                            })
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->statePath('data');
    }

    /**
     * Solo prepara la descarga y redirige al controlador.
     * Livewire responde en JSON; el archivo se descarga en una petición aparte.
     */
    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            $query = $this->buildDownloadQuery($data);

            $url = route('reportes.analisis-recorrido.download', $query);

            // Livewire v3: navigate:false para abrir la descarga sin cambiar de página
            $this->redirect($url, navigate: false);

            Notification::make()
                ->title('Generando reporte')
                ->body('La descarga del reporte se iniciará en unos segundos.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error al preparar descarga de análisis de recorrido desde Filament', [
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Error al generar el reporte')
                ->body('Ocurrió un error inesperado al preparar la descarga. Intente nuevamente.')
                ->danger()
                ->send();
        }
    }

    /**
     * Normaliza los datos del formulario al formato esperado por el controlador.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function buildDownloadQuery(array $data): array
    {
        $fechaDesde = Carbon::parse($data['desde'])->format('Y-m-d');
        $fechaHasta = Carbon::parse($data['hasta'])->format('Y-m-d');

        $horaDesde = $data['hora_desde'] ?? '00:00';
        $horaHasta = $data['hora_hasta'] ?? '23:59';

        return [
            'titulo'       => (string) ($data['titulo'] ?? ''),
            'modo'         => (string) ($data['modo'] ?? 'semanal'),
            'formato'      => (string) ($data['formato'] ?? 'excel'),
            'fecha_desde'  => $fechaDesde,
            'fecha_hasta'  => $fechaHasta,
            'hora_desde'   => $horaDesde,
            'hora_hasta'   => $horaHasta,
            'vehiculo_ids' => array_map('intval', $data['vehiculo_ids'] ?? []),
        ];
    }
}
