<?php

namespace App\Support;

/**
 * Formats digits stored in the database for reading: phones, CPF/CNPJ and CEP.
 * Values that do not have the expected length are shown as they are.
 */
final class BrazilianFormat
{
    /** "11999990000" → "(11) 99999-0000"; "1133330000" → "(11) 3333-0000". */
    public static function phone(?string $digits): ?string
    {
        return match (strlen((string) $digits)) {
            11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', (string) $digits),
            10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', (string) $digits),
            default => $digits,
        };
    }

    /** "52998224725" → "529.982.247-25"; 14 digits → "11.222.333/0001-81". */
    public static function document(?string $digits): ?string
    {
        return match (strlen((string) $digits)) {
            11 => preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', (string) $digits),
            14 => preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', (string) $digits),
            default => $digits,
        };
    }

    /** "01001000" → "01001-000". */
    public static function zip(?string $digits): ?string
    {
        return strlen((string) $digits) === 8
            ? substr((string) $digits, 0, 5).'-'.substr((string) $digits, 5)
            : $digits;
    }
}
