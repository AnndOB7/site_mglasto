<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Support\ProductListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Searches by product name and SKU (also the variants' SKU). Every word
     * typed must appear, in any order: "piso preto" finds "PISO BORRACHA PRETO".
     * Without a search term, lists every product.
     */
    public function __invoke(Request $request): Response
    {
        // % and _ are wildcards in SQL LIKE and \ escapes them; none of them
        // mean anything in a product search, so they are simply removed.
        $term = Str::of((string) $request->query('q', ''))
            ->remove(['%', '_', '\\'])
            ->squish()
            ->limit(100, '')
            ->toString();
        $sort = ProductListing::normalizeSort($request->query('ordem'));

        $query = ProductListing::query();

        foreach (array_filter(explode(' ', $term)) as $word) {
            $like = "%{$word}%";

            $query->where(fn (Builder $query) => $query
                ->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhereHas('variants', fn (Builder $variants) => $variants->where('sku', 'like', $like)));
        }

        return Inertia::render('store/search', [
            'term' => $term,
            'products' => ProductListing::paginate(ProductListing::sort($query, $sort)),
            'sort' => $sort,
            'sortOptions' => ProductListing::sortOptions(),
        ]);
    }
}
