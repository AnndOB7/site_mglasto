<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\MoneyInput;
use App\Support\Money;
use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Produto')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Slug (endereço)')
                            ->helperText('Usado na URL: /produto/slug.')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('categories')
                            ->label('Categorias')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Produtos em "Oferta" também precisam do botão "Destaque" ligado para aparecer na home.'),
                        Textarea::make('short_description')
                            ->label('Descrição curta')
                            ->rows(2)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->label('Descrição completa')
                            ->toolbarButtons([
                                ['bold', 'italic'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Preço')
                    ->description('Digite em reais, por exemplo 88,60. O sistema guarda em centavos.')
                    ->columns(2)
                    ->schema([
                        MoneyInput::make('price')
                            ->label('Preço')
                            ->required(),
                        MoneyInput::make('sale_price')
                            ->label('Preço promocional')
                            ->helperText('Deixe vazio se não estiver em promoção.')
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $sale = Money::toCents((string) $value);
                                $regular = Money::toCents((string) $get('price'));

                                if ($sale !== null && $regular !== null && $sale >= $regular) {
                                    $fail('O preço promocional precisa ser menor que o preço normal.');
                                }
                            }),
                    ]),

                Section::make('Estoque e vitrine')
                    ->columns(2)
                    ->schema([
                        Toggle::make('manage_stock')
                            ->label('Controlar estoque')
                            ->helperText('Desligado = sempre disponível.')
                            ->default(true)
                            ->live(),
                        TextInput::make('stock_quantity')
                            ->label('Quantidade em estoque')
                            ->integer()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => (bool) $get('manage_stock')),
                        Toggle::make('is_active')
                            ->label('Ativo (aparece na loja)')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Destaque (vitrine de ofertas da home)'),
                    ]),

                Section::make('Medidas para frete')
                    ->description('Opcional. Será usado no cálculo de frete.')
                    ->columns(4)
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('weight_grams')->label('Peso (g)')->integer()->minValue(0),
                        TextInput::make('length_mm')->label('Comprimento (mm)')->integer()->minValue(0),
                        TextInput::make('width_mm')->label('Largura (mm)')->integer()->minValue(0),
                        TextInput::make('height_mm')->label('Altura (mm)')->integer()->minValue(0),
                    ]),
            ]);
    }
}
