<?php

namespace App\Filament\Resources\Vehiculos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiculosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('codigo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('imei')
                    ->searchable(),
                TextColumn::make('nombre_api')
                    ->searchable(),
                TextColumn::make('marca')
                    ->searchable(),
                TextColumn::make('clase')
                    ->searchable(),
                TextColumn::make('modelo')
                    ->searchable(),
                TextColumn::make('tipo')
                    ->searchable(),
                TextColumn::make('anio')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('placas')
                    ->searchable(),
                TextColumn::make('area_asignada')
                    ->searchable(),
                TextColumn::make('responsable')
                    ->searchable(),
                TextColumn::make('gerencia_asignada')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
