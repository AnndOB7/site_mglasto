<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductListing;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /** How many non-featured products the home shows before "Ver todos". */
    private const SHOWCASE_SIZE = 12;

    public function __invoke(): Response
    {
        $featured = ProductListing::query()->featured()->orderBy('name')->limit(8)->get();

        // "Todos os produtos" never repeats what is already in "Ofertas".
        $others = ProductListing::query()->whereKeyNot($featured->modelKeys());

        return Inertia::render('store/home', [
            'categories' => $this->categories(),
            'featured' => ProductCardResource::collection($featured)->resolve(),
            'products' => ProductCardResource::collection(
                (clone $others)->orderBy('name')->limit(self::SHOWCASE_SIZE)->get()
            )->resolve(),
            'productsTotal' => Product::query()->active()->count(),
            'allProductsUrl' => route('search', absolute: false),
        ]);
    }

    /**
     * Active top-level categories that have products, each with one photo.
     *
     * @return array<int, array{name: string, url: string, products_count: int, image: ?string}>
     */
    private function categories(): array
    {
        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->withCount(['products' => fn ($query) => $query->active()])
            ->with(['products' => fn ($query) => $query->active()->whereHas('images')->with(['images' => fn ($q) => $q->limit(1)])->orderBy('name')->limit(1)])
            ->orderBy('position')
            ->get()
            ->filter(fn (Category $category): bool => $category->products_count > 0)
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'url' => route('categories.show', $category->slug, absolute: false),
                'products_count' => (int) $category->products_count,
                'image' => $category->products->first()?->images->first()?->url(),
            ])
            ->values()
            ->all();
    }
}
