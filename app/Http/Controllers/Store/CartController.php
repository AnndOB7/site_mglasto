<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\AddToCartRequest;
use App\Support\Cart;
use App\Support\CartLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function show(Cart $cart): Response
    {
        $lines = $cart->lines();

        return Inertia::render('store/cart', [
            'lines' => $lines->map(fn (CartLine $line): array => $line->toArray())->values()->all(),
            'subtotal' => $lines->sum(fn (CartLine $line): int => $line->total()),
            'canCheckout' => $lines->isNotEmpty() && $lines->every(fn (CartLine $line): bool => $line->hasEnoughStock()),
            'checkoutUrl' => route('checkout.show', absolute: false),
        ]);
    }

    public function store(AddToCartRequest $request, Cart $cart): RedirectResponse
    {
        $cart->add($request->product(), $request->variant(), $request->integer('quantity'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produto adicionado ao orçamento.']);

        return back();
    }

    public function update(Request $request, Cart $cart, string $key): RedirectResponse
    {
        abort_unless($cart->has($key), 404);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
        ]);

        $cart->update($key, (int) $validated['quantity']);

        return back();
    }

    public function destroy(Cart $cart, string $key): RedirectResponse
    {
        $cart->remove($key);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produto removido do orçamento.']);

        return back();
    }
}
