<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\BrazilianFormat;
use App\Support\Money;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Orders are history: only the status can change here. Everything else is
 * shown read-only.
 */
class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pedido')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('number')->label('Número'),
                        TextEntry::make('channel')
                            ->label('Canal')
                            ->formatStateUsing(fn (OrderChannel $state): string => $state->label()),
                        TextEntry::make('placed_at')
                            ->label('Data')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('external_id')
                            ->label('Código no marketplace')
                            ->placeholder('–'),
                        Select::make('status')
                            ->label('Status')
                            ->options(self::statusOptions())
                            ->required()
                            ->native(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Cliente')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer_name')->label('Nome'),
                        TextEntry::make('customer_phone')->label('Telefone')->placeholder('–')
                            ->formatStateUsing(fn (?string $state): ?string => BrazilianFormat::phone($state)),
                        TextEntry::make('customer_email')->label('E-mail')->placeholder('–'),
                        TextEntry::make('customer_document')->label('CPF/CNPJ')->placeholder('–')
                            ->formatStateUsing(fn (?string $state): ?string => BrazilianFormat::document($state)),
                        TextEntry::make('address')
                            ->label('Endereço')
                            ->state(fn (Order $record): string => self::address($record))
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Observações')
                            ->placeholder('–')
                            ->columnSpanFull(),
                    ]),

                Section::make('Valores')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subtotal')->label('Subtotal')
                            ->formatStateUsing(fn (int $state): string => Money::format($state)),
                        TextEntry::make('shipping_cost')->label('Frete')
                            ->formatStateUsing(fn (int $state): string => Money::format($state)),
                        TextEntry::make('discount')->label('Desconto')
                            ->formatStateUsing(fn (int $state): string => Money::format($state)),
                        TextEntry::make('total')->label('Total')
                            ->weight('bold')
                            ->formatStateUsing(fn (int $state): string => Money::format($state)),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    private static function address(Order $order): string
    {
        $street = collect([$order->shipping_street, $order->shipping_number, $order->shipping_complement])->filter()->implode(', ');
        $city = collect([$order->shipping_district, $order->shipping_city, $order->shipping_state])->filter()->implode(' – ');

        $address = collect([$street, $city, $order->shipping_zip ? 'CEP '.BrazilianFormat::zip($order->shipping_zip) : null])->filter()->implode(' · ');

        return $address !== '' ? $address : '–';
    }
}
