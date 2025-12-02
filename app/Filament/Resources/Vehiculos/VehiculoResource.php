<?php

namespace App\Filament\Resources\Vehiculos;

use App\Filament\Resources\Vehiculos\Pages\CreateVehiculo;
use App\Filament\Resources\Vehiculos\Pages\EditVehiculo;
use App\Filament\Resources\Vehiculos\Pages\ListVehiculos;
use App\Filament\Resources\Vehiculos\Schemas\VehiculoForm;
use App\Filament\Resources\Vehiculos\Tables\VehiculosTable;
use App\Models\Vehiculo;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    // Ícono en el menú
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;
    // Si OutlinedTruck no existe en tu versión, deja el que vino por defecto o usa OutlinedRectangleStack

    // Atributo que se usará como título por defecto
    protected static ?string $recordTitleAttribute = 'nombre_api';

    protected static string|UnitEnum|null $navigationGroup = 'Flota';
    protected static ?string $navigationLabel = 'Vehículos';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        // Aquí reutilizamos el esquema separado
        return VehiculoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Y aquí reutilizamos la clase de Tabla generada
        return VehiculosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehiculos::route('/'),
            'create' => CreateVehiculo::route('/create'),
            'edit' => EditVehiculo::route('/{record}/edit'),
        ];
    }
}
