<?php

namespace App\Support;

use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared rules for product lists in the store (category and search pages):
 * only active products, the photo eager loaded, sorting and pagination.
 */
final class ProductListing
{
    public const PER_PAGE = 24;

    /** @var array<string, string> value in the URL => label shown to the customer */
    public const SORTS = [
        'nome' => 'Nome (A–Z)',
        'menor-preco' => 'Menor preço',
        'maior-preco' => 'Maior preço',
    ];

    /**
     * Base query for anything the store shows: active products with their first photo.
     *
     * @return Builder<Product>
     */
    public static function query(): Builder
    {
        return Product::query()
            ->active()
            ->with(['images' => fn ($query) => $query->limit(1)]);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public static function sort(Builder $query, ?string $sort): Builder
    {
        // The price the customer pays is the sale price when there is one.
        return match ($sort) {
            'menor-preco' => $query->orderByRaw('COALESCE(sale_price, price) asc')->orderBy('name'),
            'maior-preco' => $query->orderByRaw('COALESCE(sale_price, price) desc')->orderBy('name'),
            default => $query->orderBy('name'),
        };
    }

    /**
     * @param  Builder<Product>  $query
     * @return LengthAwarePaginator<int, array<mixed>>
     */
    public static function paginate(Builder $query): LengthAwarePaginator
    {
        return $query->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Product $product): array => ProductCardResource::make($product)->resolve());
    }

    public static function normalizeSort(?string $sort): string
    {
        return array_key_exists((string) $sort, self::SORTS) ? (string) $sort : 'nome';
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function sortOptions(): array
    {
        return collect(self::SORTS)
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
