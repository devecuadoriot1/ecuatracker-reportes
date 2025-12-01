<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('device_id')
                    ->required()
                    ->numeric(),
                TextInput::make('codigo')
                    ->numeric(),
                TextInput::make('imei'),
                TextInput::make('nombre_api'),
                TextInput::make('marca'),
                TextInput::make('clase'),
                TextInput::make('modelo'),
                TextInput::make('tipo'),
                TextInput::make('anio')
                    ->numeric(),
                TextInput::make('placas'),
                TextInput::make('area_asignada'),
                TextInput::make('responsable'),
                TextInput::make('gerencia_asignada'),
            ]);
    }
}
