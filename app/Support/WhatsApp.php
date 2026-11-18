<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;

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

    /**
     * Summary of a quote order, ready to send to the store. Carries the order
     * number so the team finds it in the /admin panel.
     */
    public static function quoteMessage(Order $order): string
    {
        $items = $order->items->map(function (OrderItem $item): string {
            $name = $item->variant_name ? "{$item->product_name} ({$item->variant_name})" : $item->product_name;

            return "• {$item->quantity}x {$name} – ".Money::format($item->total);
        })->implode("\n");

        $city = trim("{$order->shipping_city}/{$order->shipping_state}", '/');

        return implode("\n", array_filter([
            "Olá! Gostaria de um orçamento. Pedido *{$order->number}*",
            '',
            $items,
            '',
            'Total dos produtos: *'.Money::format($order->total).'* (frete a combinar)',
            '',
            "Nome: {$order->customer_name}",
            $city !== '' ? "Entrega: {$city}, CEP ".BrazilianFormat::zip($order->shipping_zip) : null,
        ], fn (?string $line): bool => $line !== null));
    }
}
