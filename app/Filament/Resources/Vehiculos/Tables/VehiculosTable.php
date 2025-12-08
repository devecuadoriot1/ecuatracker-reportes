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
                    ->label('Código')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nombre_api')
                    ->label('Dispositivo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('placas')
                    ->label('Placas')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('marca')
                    ->label('Marca')
                    ->toggleable(),

                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->toggleable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->toggleable(),

                TextColumn::make('anio')
                    ->label('Año')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('area_asignada')
                    ->label('Área asignada')
                    ->toggleable(),

                TextColumn::make('responsable')
                    ->label('Responsable')
                    ->toggleable(),

                TextColumn::make('gerencia_asignada')
                    ->label('Gerencia asignada')
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
