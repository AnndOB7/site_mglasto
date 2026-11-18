<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->helperText('Usado na URL: /categoria/slug. Gerado a partir do nome.')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                Select::make('parent_id')
                    ->label('Categoria-pai')
                    ->relationship(
                        'parent',
                        'name',
                        modifyQueryUsing: fn ($query, $record) => $query->when($record, fn ($q) => $q->whereKeyNot($record->getKey())),
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('Nenhuma (categoria principal)'),
                TextInput::make('position')
                    ->label('Posição')
                    ->helperText('Menor número aparece primeiro.')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Ativa (aparece na loja)')
                    ->default(true),
            ]);
    }
}
