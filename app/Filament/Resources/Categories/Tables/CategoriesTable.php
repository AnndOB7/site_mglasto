<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Categoria-pai')
                    ->placeholder('–'),
                TextColumn::make('products_count')
                    ->label('Produtos')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Posição')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Ativa'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Ativa'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
