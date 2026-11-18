<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Data every store page needs for the header, menu and footer.
 * Shared with React through HandleInertiaRequests as the "store" prop.
 */
final class StoreLayoutData
{
    /**
     * @return array{
     *     categories: list<array{name: string, slug: string, url: string}>,
     *     cart_count: int,
     *     search_url: string,
     *     search_query: string,
     *     cart_url: string,
     *     home_url: string,
     *     institutional: list<array{label: string, url: string}>,
     *     company: array{name: string, phone: ?string, email: ?string, address: ?string, document: ?string},
     * }
     */
    public static function make(Request $request): array
    {
        return [
            'categories' => Category::query()
                ->active()
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['name', 'slug'])
                ->map(fn (Category $category): array => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'url' => "/categoria/{$category->slug}",
                ])
                ->values()
                ->all(),
            'cart_count' => self::cartCount($request),
            'search_url' => '/busca',
            'search_query' => (string) $request->query('q', ''),
            'cart_url' => '/carrinho',
            'home_url' => '/',
            'institutional' => [
                ['label' => 'Atendimento', 'url' => '/atendimento'],
                ['label' => 'Perguntas frequentes', 'url' => '/perguntas-frequentes'],
                ['label' => 'Trocas e devoluções', 'url' => '/trocas-e-devolucoes'],
            ],
            'company' => config('mglasto.company'),
        ];
    }

    private static function cartCount(Request $request): int
    {
        /** @var array<string, array{quantity: int}> $items */
        $items = $request->hasSession() ? $request->session()->get('cart', []) : [];

        return array_sum(array_column($items, 'quantity'));
    }
}
