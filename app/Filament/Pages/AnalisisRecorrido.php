<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Vehiculo;
use App\Filament\Forms\Components\VehiculosSelector;
use App\Services\Reportes\AnalisisRecorridoService; // lo puedes dejar por si luego haces previews
use Filament\Actions\Action as FormAction;
use Filament\Schemas\Components\Utilities\Set;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

        if ($desde instanceof Carbon) {
            $fechaDesde = $desde->toDateString();
        } elseif (is_string($desde) && $desde !== '') {
            $fechaDesde = Carbon::parse($desde)->toDateString();
        }

        if ($hasta instanceof Carbon) {
            $fechaHasta = $hasta->toDateString();
        } elseif (is_string($hasta) && $hasta !== '') {
            $fechaHasta = Carbon::parse($hasta)->toDateString();
        }

        $horaDesde = $data['hora_desde'] ?? null;
        $horaHasta = $data['hora_hasta'] ?? null;

        $horaDesdeStr = $horaDesde instanceof Carbon
            ? $horaDesde->format('H:i')
            : ((string) ($horaDesde ?: '00:00'));

        $horaHastaStr = $horaHasta instanceof Carbon
            ? $horaHasta->format('H:i')
            : ((string) ($horaHasta ?: '23:59'));

        return [
            'titulo'       => (string) ($data['titulo'] ?? ''),
            'modo'         => (string) ($data['modo'] ?? 'semanal'),
            'formato'      => (string) ($data['formato'] ?? 'excel'),
            'fecha_desde'  => $fechaDesde,
            'fecha_hasta'  => $fechaHasta,
            'hora_desde'   => $horaDesdeStr,
            'hora_hasta'   => $horaHastaStr,
            'vehiculo_ids' => array_map('intval', $data['vehiculo_ids'] ?? []),
        ];
    }
}
