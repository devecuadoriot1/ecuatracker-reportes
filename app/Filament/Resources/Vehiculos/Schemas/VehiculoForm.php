<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de API')
                    ->schema([
                        TextInput::make('device_id')
                            ->label('Device ID')
                            ->numeric()
                            ->required()
                            ->unique(
                                table: 'vehiculos',
                                column: 'device_id',
                                ignoreRecord: true
                            ),

                        TextInput::make('codigo')
                            ->label('Código interno')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('nombre_api')
                            ->label('Nombre en API')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('imei')
                            ->label('IMEI')
                            ->maxLength(50)
                            ->nullable()
                            ->unique(
                                table: 'vehiculos',
                                column: 'imei',
                                ignoreRecord: true
                            ),

                        TextInput::make('placas')
                            ->label('Placas')
                            ->maxLength(50)
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Características del vehículo')
                    ->schema([
                        TextInput::make('marca')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('clase')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('modelo')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('tipo')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('anio')
                            ->label('Año')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(now()->year + 1)
                            ->nullable(),
                    ])
                    ->columns(3),

                Section::make('Asignación')
                    ->schema([
                        TextInput::make('area_asignada')
                            ->label('Área asignada')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('responsable')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('gerencia_asignada')
                            ->label('Gerencia asignada')
                            ->maxLength(255)
                            ->nullable(),
                    ])
                    ->columns(3),
            ]);
    }
}
