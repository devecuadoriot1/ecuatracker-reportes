<?php

namespace App\Filament\Resources\Vehiculos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use App\Models\Vehiculo;
use Filament\Tables;
use Filament\Tables\Table;

class VehiculosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Vehiculo::query()) // Aquí luego puedes enganchar tus scopes
            ->columns([
                TextColumn::make('codigo')
                    ->label('Codigo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nombre_api')
                    ->label('Dispositivo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('placas')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('marca')
                    ->toggleable(),

                TextColumn::make('modelo')
                    ->toggleable(),

                TextColumn::make('tipo')
                    ->toggleable(),

                TextColumn::make('anio')
                    ->label('Ano')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('area_asignada')
                    ->label('Area')
                    ->toggleable(),

                TextColumn::make('responsable')
                    ->toggleable(),

                TextColumn::make('gerencia_asignada')
                    ->label('Gerencia')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('sin_placas')
                    ->label('Sin placas')
                    ->query(fn($query) => $query->whereNull('placas')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
