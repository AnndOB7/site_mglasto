<?php

namespace App\Support;

/**
 * Converts between what people type (reais, "88,60") and what the database
 * stores (integer cents, 8860).
 *
 * Every screen that reads or writes money goes through this class, so the
 * conversion lives in a single place.
 */
final class Money
{
    /**
     * "88,60" → 8860, "1.234,56" → 123456, "88" → 8800. Empty → null.
     *
     * Returns null when the text is not a valid amount, so callers can show
     * a validation error instead of silently saving a wrong price.
     */
    public static function toCents(string|int|float|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }

        $text = str_replace(['R$', ' ', "\u{00A0}"], '', trim($value));

        if (str_contains($text, ',')) {
            // Brazilian format: dots group thousands, the comma separates cents.
            if (! preg_match('/^\d{1,3}(\.\d{3})*,\d{1,2}$|^\d+,\d{1,2}$/', $text)) {
                return null;
            }

            $text = str_replace(['.', ','], ['', '.'], $text);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $text)) {
            // "1.234" with no comma: dots are thousand separators.
            $text = str_replace('.', '', $text);
        } elseif (! preg_match('/^\d+(\.\d{1,2})?$/', $text)) {
            return null;
        }

        return (int) round(((float) $text) * 100);
    }

    /**
     * 8860 → "88,60", 123456 → "1.234,56". Null → null.
     *
     * Used to fill form inputs. For display with the currency symbol use
     * {@see self::format()}.
     */
    public static function fromCents(?int $cents): ?string
    {
        if ($cents === null) {
            return null;
        }

        return number_format($cents / 100, 2, ',', '.');
    }

    /**
     * 8860 → "R$ 88,60".
     */
    public static function format(int $cents): string
    {
        return 'R$ '.self::fromCents($cents);
    }
}
