<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ProductListing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $children = $category->children()->active()->get(['id', 'name', 'slug']);
        $sort = ProductListing::normalizeSort($request->query('ordem'));

        // A category page also lists the products of its subcategories.
        $query = ProductListing::query()->whereHas(
            'categories',
            fn ($query) => $query->whereKey([$category->id, ...$children->modelKeys()]),
        );

        return Inertia::render('store/category', [
            'category' => [
                'name' => $category->name,
                'description' => $category->description,
                'url' => route('categories.show', $category->slug, absolute: false),
            ],
            'subcategories' => $children->map(fn (Category $child): array => [
                'name' => $child->name,
                'url' => route('categories.show', $child->slug, absolute: false),
            ])->all(),
            'products' => ProductListing::paginate(ProductListing::sort($query, $sort)),
            'sort' => $sort,
            'sortOptions' => ProductListing::sortOptions(),
        ]);
    }
}
