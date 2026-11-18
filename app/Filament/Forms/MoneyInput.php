<?php

namespace App\Filament\Forms;

use App\Support\Money;
use Closure;
use Filament\Forms\Components\TextInput;

/**
 * A text input where people type reais ("88,60") and the database receives
 * cents (8860).
 */
final class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('R$')
            ->placeholder('0,00')
            ->inputMode('decimal')
            ->formatStateUsing(fn (?int $state): ?string => Money::fromCents($state))
            ->dehydrateStateUsing(fn (?string $state): ?int => Money::toCents($state))
            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                if (filled($value) && Money::toCents((string) $value) === null) {
                    $fail('Informe o valor em reais, por exemplo 88,60.');
                }
            });
    }
}
