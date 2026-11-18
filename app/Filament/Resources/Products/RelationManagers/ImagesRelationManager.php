<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Fotos';

    protected static ?string $modelLabel = 'foto';

    protected static ?string $pluralModelLabel = 'fotos';

    public function form(Schema $schema): Schema
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('Foto')
                    ->image()
                    ->disk('public')
                    ->directory("products/{$product->slug}")
                    ->visibility('public')
                    ->maxSize(5120)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('alt')
                    ->label('Texto alternativo')
                    ->helperText('Descreve a foto para quem usa leitor de tela e para o Google.')
                    ->default($product->name)
                    ->maxLength(255),
                TextInput::make('position')
                    ->label('Ordem')
                    ->integer()
                    ->minValue(0)
                    ->default(fn (): int => (int) $product->images()->max('position') + 1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt')
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('path')
                    ->label('Foto')
                    ->disk('public')
                    ->square()
                    ->imageSize(64),
                TextColumn::make('alt')
                    ->label('Texto alternativo')
                    ->wrap(),
                TextColumn::make('position')
                    ->label('Ordem'),
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
