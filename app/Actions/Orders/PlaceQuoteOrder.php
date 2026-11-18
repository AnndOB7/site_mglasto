<?php

namespace App\Actions\Orders;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Cart;
use App\Support\CartLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns the cart into a quote order from the site.
 *
 * Everything happens in one transaction: if anything fails, no half-saved
 * order is left behind. Prices are read from the database again here, and
 * each item stores a COPY of name, variant, SKU and price (rule 4), so later
 * product changes never alter this order.
 */
class PlaceQuoteOrder
{
    public function __construct(private readonly Cart $cart) {}

    /**
     * @param  array{name: string, phone: string, email?: ?string, document?: ?string, zip: string, street: string, number: string, complement?: ?string, district: string, city: string, state: string, notes?: ?string}  $customer
     *
     * @throws ValidationException when the cart is empty or an item ran out of stock
     */
    public function handle(array $customer): Order
    {
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Seu orçamento está vazio.']);
        }

        $missing = $lines->reject(fn (CartLine $line): bool => $line->hasEnoughStock());

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Sem estoque suficiente para: '.$missing->map(fn (CartLine $line): string => $line->product->name)->implode(', ').'. Ajuste as quantidades no orçamento.',
            ]);
        }

        $subtotal = $lines->sum(fn (CartLine $line): int => $line->total());

        $order = DB::transaction(function () use ($customer, $lines, $subtotal): Order {
            $order = Order::create([
                // The readable number needs the id, which only exists after the insert.
                'number' => 'TMP-'.Str::uuid(),
                'channel' => OrderChannel::Site,
                'status' => OrderStatus::Quote,
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'customer_email' => $customer['email'] ?? null,
                'customer_document' => $customer['document'] ?? null,
                'shipping_zip' => $customer['zip'],
                'shipping_street' => $customer['street'],
                'shipping_number' => $customer['number'],
                'shipping_complement' => $customer['complement'] ?? null,
                'shipping_district' => $customer['district'],
                'shipping_city' => $customer['city'],
                'shipping_state' => $customer['state'],
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'notes' => $customer['notes'] ?? null,
                'placed_at' => now(),
            ]);

            $order->update(['number' => self::number($order->id)]);

            $order->items()->createMany($lines->map(fn (CartLine $line): array => [
                'product_id' => $line->product->id,
                'product_variant_id' => $line->variant?->id,
                'product_name' => $line->product->name,
                'variant_name' => $line->variant?->name,
                'sku' => $line->variant->sku ?? $line->product->sku,
                'unit_price' => $line->unitPrice(),
                'quantity' => $line->quantity,
                'total' => $line->total(),
            ])->all());

            return $order;
        });

        $this->cart->clear();

        return $order;
    }

    /**
     * 123 → "MGL-000123".
     */
    public static function number(int $id): string
    {
        return 'MGL-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
