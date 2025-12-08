<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\VehiculosSelector;
use App\Services\Reportes\ParametrizacionKmService;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            'titulo'     => 'Análisis de recorrido',
            'modo'       => 'semanal',
            'formato'    => 'excel',
            'hora_desde' => '00:00',
            'hora_hasta' => '23:59',
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
                        'semanal' => 'Semanal',
                        'mensual' => 'Mensual',
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

                VehiculosSelector::make('vehiculo_ids')
                    ->label('Vehículos')
                    ->required()
                    // puedes ajustar cuántos grupos por página quieres
                    ->groupsPerPage(2),
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
            // Normalizamos el payload al formato que espera el controlador
            $payload = $this->buildReportPayload($data);

            // Generamos un token único y guardamos los datos en cache (10 minutos)
            $token = (string) Str::uuid();

            Cache::put("analisis_recorrido:{$token}", $payload, now()->addMinutes(10));

            // Construimos una URL corta con solo el token
            $url = route('reportes.analisis-recorrido.download', ['token' => $token]);

            // Livewire v3: navigate:false para abrir la descarga sin cambiar de página
            $this->redirect($url, navigate: false);

            Notification::make()
                ->title('Generando reporte')
                ->body('La descarga del reporte se iniciará en unos segundos.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error al preparar descarga de análisis de recorrido desde Filament', [
                'titulo'       => $data['titulo'] ?? null,
                'modo'         => $data['modo'] ?? null,
                'formato'      => $data['formato'] ?? null,
                'vehiculos'    => is_array($data['vehiculo_ids'] ?? null) ? count($data['vehiculo_ids']) : null,
                'error'        => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Reporte en proceso')
                ->body('La descarga del reporte debería iniciarse en unos segundos. Si no ocurre, revise su bloqueador de pop-ups.')
                ->success()
                ->send();

            Notification::make()
                ->title('Error al generar el reporte')
                ->body('Ocurrió un error inesperado al preparar la descarga. Intente nuevamente.')
                ->danger()
                ->send();
        }
    }

    /**
     * Normaliza el estado del formulario Filament al formato que
     * espera el controlador (titulo, modo, formato, fecha_*, hora_*, vehiculo_ids).
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function buildReportPayload(array $data): array
    {
        $desde = $data['desde'] ?? null;
        $hasta = $data['hasta'] ?? null;

        $fechaDesde = '';
        $fechaHasta = '';

        try {
            if ($desde instanceof Carbon) {
                $fechaDesde = $desde->toDateString();
            } elseif (is_string($desde) && $desde !== '') {
                $fechaDesde = Carbon::parse($desde)->toDateString();
            }
        } catch (\Throwable $e) {
            // Si algo raro pasa, dejamos fechaDesde vacío; el controller lo validará
            $fechaDesde = '';
        }

        try {
            if ($hasta instanceof Carbon) {
                $fechaHasta = $hasta->toDateString();
            } elseif (is_string($hasta) && $hasta !== '') {
                $fechaHasta = Carbon::parse($hasta)->toDateString();
            }
        } catch (\Throwable $e) {
            $fechaHasta = '';
        }

        $horaDesde = $data['hora_desde'] ?? null;
        $horaHasta = $data['hora_hasta'] ?? null;

        $horaDesdeStr = $horaDesde instanceof Carbon
            ? $horaDesde->format('H:i')
            : ((string) ($horaDesde ?: '00:00'));

        $horaHastaStr = $horaHasta instanceof Carbon
            ? $horaHasta->format('H:i')
            : ((string) ($horaHasta ?: '23:59'));

        $titulo  = trim((string) ($data['titulo'] ?? ''));
        $modo    = strtolower((string) ($data['modo'] ?? 'semanal'));
        $formato = strtolower((string) ($data['formato'] ?? 'excel'));

        $vehiculoIds = collect($data['vehiculo_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return [
            'titulo'       => $titulo,
            'modo'         => $modo,
            'formato'      => $formato,
            'fecha_desde'  => $fechaDesde,
            'fecha_hasta'  => $fechaHasta,
            'hora_desde'   => $horaDesdeStr,
            'hora_hasta'   => $horaHastaStr,
            'vehiculo_ids' => $vehiculoIds,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('configurar_parametrizaciones_km')
                ->label('Configurar rangos de km')
                ->icon('heroicon-m-adjustments-horizontal')
                ->modalHeading('Configurar rangos de parametrización de km')
                ->modalWidth('3xl')
                ->form([
                    Section::make('Rangos semanales')
                        ->description('Se usan para cada semana y para el promedio mensual.')
                        ->schema([
                            Forms\Components\Repeater::make('semana')
                                ->label('Rangos de km por semana')
                                ->schema([
                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre')
                                        ->required()
                                        ->maxLength(50),

                                    Forms\Components\TextInput::make('km_min')
                                        ->label('Km mínimo')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0),

                                    Forms\Components\TextInput::make('km_max')
                                        ->label('Km máximo')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0),

                                    Forms\Components\TextInput::make('orden')
                                        ->label('Orden')
                                        ->numeric()
                                        ->required()
                                        ->default(0),
                                ])
                                ->columns(4)
                                ->default([]),
                        ]),

                    Section::make('Rangos mensuales (total)')
                        ->description('Se usan para el total mensual de km.')
                        ->schema([
                            Forms\Components\Repeater::make('mes_total')
                                ->label('Rangos de km por mes (total)')
                                ->schema([
                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre')
                                        ->required()
                                        ->maxLength(50),

                                    Forms\Components\TextInput::make('km_min')
                                        ->label('Km mínimo')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0),

                                    Forms\Components\TextInput::make('km_max')
                                        ->label('Km máximo')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0),

                                    Forms\Components\TextInput::make('orden')
                                        ->label('Orden')
                                        ->numeric()
                                        ->required()
                                        ->default(0),
                                ])
                                ->columns(4)
                                ->default([]),
                        ]),
                ])
                ->mountUsing(function (Schema $form, ParametrizacionKmService $service) {
                    $form->fill($service->getRangosParaFormulario());
                })
                ->action(function (array $data, ParametrizacionKmService $service) {
                    try {
                        $service->actualizarRangosDesdeFormulario($data);

                        Notification::make()
                            ->title('Parametrización actualizada')
                            ->body('Los rangos de km se han guardado correctamente.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::error('Error al actualizar parametrización de km desde AnalisisRecorrido', [
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Error al guardar parametrización')
                            ->body('Ocurrió un error al guardar los rangos. Verifique los datos e intente nuevamente.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
