<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The fields a product card needs (home, category and search pages).
 * Prices go to the browser in cents; the browser only formats them.
 *
 * @mixin Product
 */
class ProductCardResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, url: string, price: int, sale_price: ?int, in_stock: bool, image: ?array{url: string, alt: string}}
     */
    public function toArray(Request $request): array
    {
        $image = $this->images->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => route('products.show', $this->slug, absolute: false),
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'in_stock' => $this->isInStock(),
            'image' => $image ? [
                'url' => $image->url(),
                'alt' => $image->alt ?: $this->name,
            ] : null,
        ];
    }
}
