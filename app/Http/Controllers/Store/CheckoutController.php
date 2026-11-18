<?php

namespace App\Http\Controllers\Store;

use App\Actions\Orders\PlaceQuoteOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Cart;
use App\Support\CartLine;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /** Session key that proves this visitor placed the order. */
    private const PLACED_ORDERS = 'placed_orders';

    public function show(Cart $cart): Response|RedirectResponse
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return to_route('cart.show');
        }

        return Inertia::render('store/checkout', [
            'lines' => $lines->map(fn (CartLine $line): array => $line->toArray())->values()->all(),
            'subtotal' => $lines->sum(fn (CartLine $line): int => $line->total()),
            'states' => CheckoutRequest::STATES,
            'submitUrl' => route('checkout.store', absolute: false),
            'cartUrl' => route('cart.show', absolute: false),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceQuoteOrder $placeOrder): RedirectResponse
    {
        $order = $placeOrder->handle($request->customer());

        $placed = (array) $request->session()->get(self::PLACED_ORDERS, []);
        $request->session()->put(self::PLACED_ORDERS, [...$placed, $order->id]);

        return to_route('checkout.done', $order->number);
    }

    /**
     * Confirmation page. Order numbers are sequential and easy to guess, so it
     * only opens in the session that placed the order: nobody can read other
     * customers' names, phones and addresses by changing the number in the URL.
     */
    public function done(Request $request, Order $order): Response
    {
        abort_unless(in_array($order->id, (array) $request->session()->get(self::PLACED_ORDERS, []), true), 404);

        $order->load('items');

        return Inertia::render('store/checkout-done', [
            'order' => [
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'total' => $order->total,
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'name' => $item->product_name,
                    'variant' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                ])->all(),
            ],
            'whatsappUrl' => WhatsApp::link(WhatsApp::quoteMessage($order)),
            'homeUrl' => route('home', absolute: false),
        ]);
    }
}
