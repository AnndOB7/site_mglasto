<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\HtmlSanitizer;
use App\Support\ProductListing;
use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const RELATED_LIMIT = 4;

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'images',
            'categories' => fn ($query) => $query->active()->orderBy('position'),
            'variants' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
        ]);

        // "Oferta" is a showcase, not what the product is: prefer a real category for the breadcrumb.
        $category = $product->categories->sortBy(fn ($category): bool => $category->slug === 'oferta')->first();
        $url = route('products.show', $product->slug);
        $summary = $this->summary($product);
        $image = $product->images->first();

        return Inertia::render('store/product', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                // Rule 8: imported HTML is cleaned again before it is shown.
                'description_html' => HtmlSanitizer::clean($product->description),
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'in_stock' => $product->isInStock(),
                'max_quantity' => $product->manage_stock ? max(0, (int) $product->stock_quantity) : null,
                'images' => $product->images->map(fn (ProductImage $image): array => [
                    'url' => $image->url(),
                    'alt' => $image->alt ?: $product->name,
                ])->all(),
                'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'options' => $variant->options,
                    'price' => $variant->price,
                    'in_stock' => $variant->setRelation('product', $product)->isInStock(),
                ])->all(),
                'option_groups' => $this->optionGroups($product),
            ],
            'category' => $category ? [
                'name' => $category->name,
                'url' => route('categories.show', $category->slug, absolute: false),
            ] : null,
            'related' => ProductCardResource::collection($this->related($product))->resolve(),
            'whatsappUrl' => WhatsApp::link("Olá! Tenho uma dúvida sobre o produto {$product->name}: {$url}"),
            'addToCartUrl' => route('cart.items.store', absolute: false),
        ])->withViewData('meta', [
            'title' => $product->name,
            'description' => $summary,
            'image' => $image ? url($image->url()) : null,
            'url' => $url,
            'type' => 'product',
        ]);
    }

    /**
     * Text for search engines and link previews (WhatsApp, Facebook).
     */
    private function summary(Product $product): string
    {
        $text = $product->short_description ?: strip_tags((string) $product->description);

        return Str::limit(Str::squish(html_entity_decode($text)), 160) ?: "{$product->name} na loja MGLASTO.";
    }

    /**
     * Groups the variant options for the selector, keeping the order they appear:
     * [['name' => 'Cor', 'values' => ['Amarelo', 'Azul', ...]], ...].
     *
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    private function optionGroups(Product $product): array
    {
        $groups = [];

        foreach ($product->variants as $variant) {
            foreach ($variant->options as $name => $value) {
                $groups[$name] ??= [];

                if (! in_array($value, $groups[$name], true)) {
                    $groups[$name][] = (string) $value;
                }
            }
        }

        return collect($groups)
            ->map(fn (array $values, string $name): array => ['name' => $name, 'values' => $values])
            ->values()
            ->all();
    }

    /**
     * Other active products from the same categories.
     *
     * @return Collection<int, Product>
     */
    private function related(Product $product): Collection
    {
        $categoryIds = $product->categories->modelKeys();

        if ($categoryIds === []) {
            return new Collection;
        }

        return ProductListing::query()
            ->whereKeyNot($product->id)
            ->whereHas('categories', fn ($query) => $query->whereKey($categoryIds))
            ->orderBy('name')
            ->limit(self::RELATED_LIMIT)
            ->get();
    }
}
