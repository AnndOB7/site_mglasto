<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Prices are integers in cents: 8860 = R$ 88,60.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $sku
 * @property string|null $short_description
 * @property string|null $description
 * @property int $price
 * @property int|null $sale_price
 * @property bool $manage_stock
 * @property int|null $stock_quantity
 * @property int|null $weight_grams
 * @property int|null $length_mm
 * @property int|null $width_mm
 * @property int|null $height_mm
 * @property bool $is_active
 * @property bool $is_featured
 * @property int|null $woo_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read Collection<int, ProductImage> $images
 */
#[Fillable([
    'name', 'slug', 'sku', 'short_description', 'description', 'price', 'sale_price',
    'manage_stock', 'stock_quantity', 'weight_grams', 'length_mm', 'width_mm', 'height_mm',
    'is_active', 'is_featured', 'woo_id',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manage_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * The price the customer pays: the sale price when there is one.
     */
    public function currentPrice(): int
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Whether the product can be added to the cart.
     */
    public function isInStock(): bool
    {
        return ! $this->manage_stock || ($this->stock_quantity ?? 0) > 0;
    }

    /**
     * Only products visible in the store.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Only products highlighted on the home page.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }
}
