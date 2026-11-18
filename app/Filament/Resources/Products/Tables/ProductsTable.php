<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('images'))
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->square()
                    ->getStateUsing(fn (Product $record): ?string => $record->images->first()?->path),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Product $record): ?string => $record->sku),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL', divideBy: 100, locale: 'pt_BR')
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('Promoção')
                    ->money('BRL', divideBy: 100, locale: 'pt_BR')
                    ->placeholder('–')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('Estoque')
                    ->state(fn (Product $record): string => $record->manage_stock
                        ? (string) ($record->stock_quantity ?? 0)
                        : 'Sem controle')
                    ->badge()
                    ->color(fn (Product $record): string => $record->isInStock() ? 'success' : 'danger'),
                ToggleColumn::make('is_active')
                    ->label('Ativo'),
                ToggleColumn::make('is_featured')
                    ->label('Destaque'),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->label('Categoria')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Ativo'),
                TernaryFilter::make('is_featured')
                    ->label('Destaque'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
