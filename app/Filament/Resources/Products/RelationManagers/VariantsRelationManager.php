<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Forms\MoneyInput;
use App\Models\ProductVariant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variações';

    protected static ?string $modelLabel = 'variação';

    protected static ?string $pluralModelLabel = 'variações';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->helperText('Ex.: Preto / 3 mm')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sku')
                    ->label('SKU')
                    ->helperText('Deixe vazio se for igual ao do produto.')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                KeyValue::make('options')
                    ->label('Opções')
                    ->keyLabel('Atributo (ex.: Cor)')
                    ->valueLabel('Valor (ex.: Preto)')
                    ->required()
                    ->columnSpanFull(),
                MoneyInput::make('price')
                    ->label('Preço próprio')
                    ->helperText('Vazio = usa o preço do produto.'),
                TextInput::make('stock_quantity')
                    ->label('Estoque próprio')
                    ->helperText('Vazio = compartilha o estoque do produto.')
                    ->integer()
                    ->minValue(0),
                Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('Igual ao produto'),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL', divideBy: 100, locale: 'pt_BR')
                    ->placeholder('Preço do produto'),
                TextColumn::make('stock_quantity')
                    ->label('Estoque')
                    ->placeholder('Compartilhado')
                    ->state(fn (ProductVariant $record): ?int => $record->stock_quantity),
                ToggleColumn::make('is_active')
                    ->label('Ativa'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
