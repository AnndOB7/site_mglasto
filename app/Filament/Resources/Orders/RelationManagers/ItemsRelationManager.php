<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only: order items are a copy of the product at purchase time.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Itens';

    protected static ?string $modelLabel = 'item';

    protected static ?string $pluralModelLabel = 'itens';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Produto')
                    ->wrap()
                    ->description(fn ($record): ?string => $record->variant_name),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('–'),
                TextColumn::make('unit_price')
                    ->label('Preço unitário')
                    ->money('BRL', divideBy: 100, locale: 'pt_BR'),
                TextColumn::make('quantity')
                    ->label('Qtd.'),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL', divideBy: 100, locale: 'pt_BR'),
            ])
            ->paginated(false);
    }
}
