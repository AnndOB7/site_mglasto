<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The quote cart, kept in the visitor's session (no login needed).
 *
 * The session only stores WHAT was chosen (product, variant, quantity).
 * Prices are always read again from the database, so a price sent by the
 * browser is never trusted and price changes show up immediately.
 */
final class Cart
{
    public const SESSION_KEY = 'cart';

    public const MAX_QUANTITY = 999;

    public function __construct(private readonly Session $session) {}

    /**
     * Adds a product (or one of its variants). Adding the same item again sums the quantities.
     */
    public function add(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $items = $this->items();
        $key = self::key($product->id, $variant?->id);
        $current = $items[$key]['quantity'] ?? 0;

        $items[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => min(self::MAX_QUANTITY, $current + $quantity),
        ];

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function update(string $key, int $quantity): void
    {
        $items = $this->items();

        if (! isset($items[$key])) {
            return;
        }

        $items[$key]['quantity'] = max(1, min(self::MAX_QUANTITY, $quantity));
        $this->session->put(self::SESSION_KEY, $items);
    }

    public function remove(string $key): void
    {
        $items = $this->items();
        unset($items[$key]);
        $this->session->put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function has(string $key): bool
    {
        return isset($this->items()[$key]);
    }

    /**
     * The cart lines with product data and prices read now from the database.
     * Items whose product or variant is no longer for sale are dropped.
     *
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        $items = $this->items();

        if ($items === []) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->whereKey(array_column($items, 'product_id'))
            ->with(['images' => fn ($query) => $query->limit(1), 'variants'])
            ->get()
            ->keyBy('id');

        $lines = collect();

        foreach ($items as $key => $item) {
            $product = $products->get($item['product_id']);

            if ($product === null) {
                continue;
            }

            $variant = null;

            if ($item['variant_id'] !== null) {
                $variant = $product->variants->firstWhere('id', $item['variant_id']);

                if ($variant === null || ! $variant->is_active) {
                    continue;
                }

                $variant->setRelation('product', $product);
            }

            $lines->push(new CartLine((string) $key, $product, $variant, $item['quantity']));
        }

        return $lines;
    }

    public function subtotal(): int
    {
        return $this->lines()->sum(fn (CartLine $line): int => $line->total());
    }

    public function count(): int
    {
        return array_sum(array_column($this->items(), 'quantity'));
    }

    public static function key(int $productId, ?int $variantId): string
    {
        return $productId.'-'.($variantId ?? 0);
    }

    /**
     * @return array<string, array{product_id: int, variant_id: ?int, quantity: int}>
     */
    private function items(): array
    {
        $items = $this->session->get(self::SESSION_KEY, []);

        return is_array($items) ? $items : [];
    }
}
