<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (OrderChannel $state): string => $state->label()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Quote => 'warning',
                        OrderStatus::Cancelled => 'danger',
                        OrderStatus::Delivered => 'success',
                        default => 'info',
                    }),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->customer_phone),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL', divideBy: 100, locale: 'pt_BR')
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options(collect(OrderChannel::cases())
                        ->mapWithKeys(fn (OrderChannel $channel): array => [$channel->value => $channel->label()])
                        ->all()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(OrderForm::statusOptions()),
            ])
            ->recordActions([
                EditAction::make()->label('Abrir'),
            ]);
    }
}
