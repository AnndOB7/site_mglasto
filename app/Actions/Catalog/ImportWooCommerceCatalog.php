<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies categories, products, variations and images from the current
 * WooCommerce store through its public Store API.
 *
 * Safe to run more than once: records are matched by their WooCommerce id.
 */
class ImportWooCommerceCatalog
{
    private const FEATURED_CATEGORY_SLUG = 'oferta';

    private const ALLOWED_HTML_TAGS = '<p><br><strong><b><em><i><ul><ol><li><h2><h3><h4>';

    /** @var array{categories: int, products: int, variants: int, images: int} */
    private array $stats = ['categories' => 0, 'products' => 0, 'variants' => 0, 'images' => 0];

    /**
     * @return array{categories: int, products: int, variants: int, images: int}
     */
    public function handle(string $storeUrl, bool $withImages = true): array
    {
        $client = Http::baseUrl(rtrim($storeUrl, '/').'/wp-json/wc/store/v1')
            ->acceptJson()
            ->timeout(30)
            ->retry(3, 500);

        $categoryIds = $this->importCategories($client);
        $membership = $this->fetchCategoryMembership($client, array_keys($categoryIds));

        foreach ($this->fetchProducts($client) as $data) {
            $wooCategoryIds = array_unique([
                ...array_column($data['categories'], 'id'),
                ...($membership[$data['id']] ?? []),
            ]);

            DB::transaction(fn () => $this->importProduct($client, $data, $wooCategoryIds, $categoryIds, $withImages));
        }

        return $this->stats;
    }

    /**
     * @return array<int, int> WooCommerce category id => local category id
     */
    private function importCategories(PendingRequest $client): array
    {
        $remote = $client->get('products/categories', ['per_page' => 100])->throw()->json();
        $map = [];

        foreach ($remote as $position => $data) {
            $category = Category::updateOrCreate(
                ['woo_id' => $data['id']],
                [
                    'name' => html_entity_decode($data['name']),
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?: null,
                    'position' => $position,
                ],
            );

            $map[$data['id']] = $category->id;
            $this->stats['categories']++;
        }

        foreach ($remote as $data) {
            Category::whereKey($map[$data['id']])->update([
                'parent_id' => $map[$data['parent']] ?? null,
            ]);
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function fetchProducts(PendingRequest $client, array $filters = []): array
    {
        $products = [];
        $page = 1;

        do {
            $batch = $client->get('products', [...$filters, 'per_page' => 100, 'page' => $page++])->throw()->json();
            $products = [...$products, ...$batch];
        } while (count($batch) === 100);

        return $products;
    }

    /**
     * The Store API leaves some categories (e.g. "Oferta") out of each product's
     * own category list, so ask every category which products it holds.
     *
     * @param  array<int, int>  $wooCategoryIds
     * @return array<int, array<int, int>> WooCommerce product id => WooCommerce category ids
     */
    private function fetchCategoryMembership(PendingRequest $client, array $wooCategoryIds): array
    {
        $membership = [];

        foreach ($wooCategoryIds as $wooCategoryId) {
            foreach ($this->fetchProducts($client, ['category' => $wooCategoryId]) as $product) {
                $membership[$product['id']][] = $wooCategoryId;
            }
        }

        return $membership;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $wooCategoryIds
     * @param  array<int, int>  $categoryIds  WooCommerce category id => local category id
     */
    private function importProduct(PendingRequest $client, array $data, array $wooCategoryIds, array $categoryIds, bool $withImages): void
    {
        $localCategoryIds = collect($wooCategoryIds)->map(fn (int $id) => $categoryIds[$id] ?? null)->filter()->values()->all();
        [$manageStock, $stock] = $this->parseStock($data);

        $product = Product::withTrashed()->updateOrCreate(
            ['woo_id' => $data['id']],
            [
                'name' => html_entity_decode($data['name']),
                'slug' => $data['slug'],
                'sku' => $data['sku'] ?: null,
                'short_description' => trim(strip_tags($data['short_description'])) ?: null,
                'description' => $this->cleanHtml($data['description']),
                'price' => (int) $data['prices']['regular_price'],
                'sale_price' => $data['on_sale'] ? (int) $data['prices']['sale_price'] : null,
                'manage_stock' => $manageStock,
                'stock_quantity' => $stock,
                'is_active' => true,
                'is_featured' => Category::whereKey($localCategoryIds)->where('slug', self::FEATURED_CATEGORY_SLUG)->exists(),
            ],
        );

        $product->categories()->sync($localCategoryIds);

        $this->stats['products']++;

        if ($data['type'] === 'variable') {
            $this->importVariants($client, $product, $data);
        }

        if ($withImages) {
            $this->importImages($product, $data['images']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importVariants(PendingRequest $client, Product $product, array $data): void
    {
        // Arr::wrap turns a missing list (null) into [], as collect() did before.
        $termNames = collect(Arr::wrap($data['attributes']))->mapWithKeys(fn (array $attribute) => [
            $attribute['name'] => collect(Arr::wrap($attribute['terms'] ?? null))->pluck('name', 'slug'),
        ]);

        foreach ($data['variations'] as $variation) {
            $remote = $client->get("products/{$variation['id']}")->throw()->json();

            $options = collect(Arr::wrap($variation['attributes']))->mapWithKeys(fn (array $a) => [
                $a['name'] => $termNames[$a['name']][$a['value']] ?? Str::headline($a['value']),
            ])->all();

            [, $stock] = $this->parseStock($remote);
            $price = (int) $remote['prices']['price'];

            // Variations that only repeat the parent's stock share it instead of owning a copy.
            if ($product->manage_stock && $stock === $product->stock_quantity && $remote['is_in_stock']) {
                $stock = null;
            }

            ProductVariant::updateOrCreate(
                ['woo_id' => $remote['id']],
                [
                    'product_id' => $product->id,
                    'name' => implode(' / ', $options),
                    'sku' => $this->uniqueVariantSku($remote['sku'], $product, $remote['id']),
                    'options' => $options,
                    'price' => $price === $product->currentPrice() ? null : $price,
                    'stock_quantity' => $stock,
                    'is_active' => (bool) $remote['is_in_stock'],
                ],
            );

            $this->stats['variants']++;
        }
    }

    /**
     * In the current store all variations repeat the parent SKU, which would
     * break the unique index. Only keep a SKU that is really the variant's own.
     */
    private function uniqueVariantSku(?string $sku, Product $product, int $wooId): ?string
    {
        if (blank($sku) || $sku === $product->sku) {
            return null;
        }

        $taken = ProductVariant::where('sku', $sku)->where('woo_id', '!=', $wooId)->exists();

        return $taken ? null : $sku;
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     */
    private function importImages(Product $product, array $images): void
    {
        $disk = Storage::disk('public');
        $keep = [];

        foreach (array_values($images) as $position => $image) {
            $extension = pathinfo(parse_url($image['src'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
            $path = "products/{$product->slug}/{$image['id']}.".strtolower($extension);

            if (! $disk->exists($path)) {
                $disk->put($path, Http::timeout(60)->retry(2, 500)->get($image['src'])->throw()->body());
                $this->stats['images']++;
            }

            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'path' => $path],
                ['alt' => $image['alt'] ?: $product->name, 'position' => $position],
            );

            $keep[] = $path;
        }

        $product->images()->whereNotIn('path', $keep)->delete();
    }

    /**
     * The Store API only exposes stock as text, e.g. "92 em estoque".
     *
     * @param  array<string, mixed>  $data
     * @return array{0: bool, 1: int|null} [manage_stock, stock_quantity]
     */
    private function parseStock(array $data): array
    {
        if (! $data['is_in_stock']) {
            return [true, 0];
        }

        if (preg_match('/(\d+)/', $data['stock_availability']['text'] ?? '', $matches)) {
            return [true, (int) $matches[1]];
        }

        return [false, null];
    }

    /**
     * Keep only simple formatting tags and drop every attribute (style, onclick...).
     */
    private function cleanHtml(?string $html): ?string
    {
        $clean = strip_tags((string) $html, self::ALLOWED_HTML_TAGS);
        $clean = trim(preg_replace('/<(\w+)\b[^>]*>/', '<$1>', $clean));

        return $clean !== '' ? $clean : null;
    }
}
