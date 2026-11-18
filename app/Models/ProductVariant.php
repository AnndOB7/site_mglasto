<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string|null $sku
 * @property array<string, string> $options Ex.: ['Cor' => 'Preto', 'Espessura' => '3 mm']
 * @property int|null $price
 * @property int|null $stock_quantity
 * @property bool $is_active
 * @property int|null $woo_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 */
#[Fillable(['product_id', 'name', 'sku', 'options', 'price', 'stock_quantity', 'is_active', 'woo_id'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The variant's own price, or the product's when the variant has none.
     */
    public function currentPrice(): int
    {
        return $this->price ?? $this->product->currentPrice();
    }
}
