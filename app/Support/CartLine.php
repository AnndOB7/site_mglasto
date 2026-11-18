<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * One line of the cart, with the price read from the database.
 */
final class CartLine
{
    public function __construct(
        public readonly string $key,
        public readonly Product $product,
        public readonly ?ProductVariant $variant,
        public readonly int $quantity,
    ) {}

    /**
     * Price per unit in cents: the variant's own price, or the product's current price.
     */
    public function unitPrice(): int
    {
        return $this->variant?->currentPrice() ?? $this->product->currentPrice();
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function isInStock(): bool
    {
        return $this->variant?->isInStock() ?? $this->product->isInStock();
    }

    /**
     * How many units can be ordered: null when the stock is not controlled.
     */
    public function availableQuantity(): ?int
    {
        if (! $this->product->manage_stock) {
            return null;
        }

        if ($this->variant !== null && $this->variant->stock_quantity !== null) {
            return max(0, $this->variant->stock_quantity);
        }

        return max(0, (int) $this->product->stock_quantity);
    }

    public function hasEnoughStock(): bool
    {
        $available = $this->availableQuantity();

        return $this->isInStock() && ($available === null || $this->quantity <= $available);
    }

    /**
     * @return array{key: string, product_id: int, name: string, variant: ?string, sku: ?string, url: string, image: ?array{url: string, alt: string}, unit_price: int, quantity: int, total: int, max_quantity: int, in_stock: bool, enough_stock: bool, item_url: string}
     */
    public function toArray(): array
    {
        $image = $this->product->images->first();

        return [
            'key' => $this->key,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'variant' => $this->variant?->name,
            'sku' => $this->variant->sku ?? $this->product->sku,
            'url' => route('products.show', $this->product->slug, absolute: false),
            'image' => $image ? ['url' => $image->url(), 'alt' => $image->alt ?: $this->product->name] : null,
            'unit_price' => $this->unitPrice(),
            'quantity' => $this->quantity,
            'total' => $this->total(),
            'max_quantity' => min(Cart::MAX_QUANTITY, $this->availableQuantity() ?? Cart::MAX_QUANTITY),
            'in_stock' => $this->isInStock(),
            'enough_stock' => $this->hasEnoughStock(),
            'item_url' => route('cart.items.update', $this->key, absolute: false),
        ];
    }
}
