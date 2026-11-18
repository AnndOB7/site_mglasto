<?php

namespace App\Support;

/**
 * Builds "click to chat" links (https://wa.me/...) with a ready message.
 */
final class WhatsApp
{
    /**
     * The store's WhatsApp number with digits only, or null when not configured.
     */
    public static function number(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) config('mglasto.whatsapp_number'));

        return $digits !== null && $digits !== '' ? $digits : null;
    }

    /**
     * Link that opens WhatsApp with the message typed in. Null when the store
     * has no number configured: the page then shows "[A DEFINIR]" instead.
     */
    public static function link(string $message): ?string
    {
        $number = self::number();

        if ($number === null) {
            return null;
        }

        return "https://wa.me/{$number}?text=".rawurlencode($message);
    }
}
