<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

test('adding a product stores only ids and quantity, and prices come from the database', function () {
    $product = Product::factory()->create(['price' => 10_000, 'sale_price' => 8_860]);

    // A tampered request trying to set its own price.
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 2, 'price' => 1, 'unit_price' => 1])
        ->assertRedirect()
        ->assertSessionHas('cart', [
            "{$product->id}-0" => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2],
        ]);

    $this->get('/carrinho')->assertInertia(fn (Assert $page) => $page
        ->component('store/cart')
        ->where('lines.0.unit_price', 8_860)
        ->where('lines.0.total', 17_720)
        ->where('subtotal', 17_720)
        ->where('canCheckout', true));
});

test('adding the same item twice sums the quantities', function () {
    $product = Product::factory()->create(['manage_stock' => false]);

    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 2]);
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 3]);

    $this->get('/carrinho')->assertInertia(fn (Assert $page) => $page->has('lines', 1)->where('lines.0.quantity', 5));
});

test('a price change after adding is reflected in the cart', function () {
    $product = Product::factory()->create(['price' => 5_000, 'sale_price' => null]);
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 1]);

    $product->update(['price' => 6_000]);

    $this->get('/carrinho')->assertInertia(fn (Assert $page) => $page->where('subtotal', 6_000));
});

test('a variant uses its own price, or the product price when it has none', function () {
    $product = Product::factory()->create(['price' => 10_000, 'sale_price' => null]);
    $own = ProductVariant::factory()->for($product)->create(['price' => 9_500]);
    $shared = ProductVariant::factory()->for($product)->create(['price' => null]);

    $this->post('/carrinho/itens', ['product_id' => $product->id, 'variant_id' => $own->id, 'quantity' => 1]);
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'variant_id' => $shared->id, 'quantity' => 1]);

    $this->get('/carrinho')->assertInertia(fn (Assert $page) => $page->where('subtotal', 19_500)->has('lines', 2));
});

test('a product with variants requires choosing one of its own active variants', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create();
    $inactive = ProductVariant::factory()->for($product)->create(['is_active' => false]);
    $otherProductVariant = ProductVariant::factory()->create();

    foreach ([null, $inactive->id, $otherProductVariant->id] as $variantId) {
        $this->post('/carrinho/itens', ['product_id' => $product->id, 'variant_id' => $variantId, 'quantity' => 1])
            ->assertSessionHasErrors(['variant_id' => 'Escolha uma opção do produto.']);
    }

    $this->assertEmpty(session('cart', []));
});

test('inactive, unknown and sold-out products cannot be added', function () {
    $inactive = Product::factory()->create(['is_active' => false]);
    $soldOut = Product::factory()->outOfStock()->create();

    $this->post('/carrinho/itens', ['product_id' => $inactive->id, 'quantity' => 1])
        ->assertSessionHasErrors(['product_id' => 'Este produto não está mais disponível.']);
    $this->post('/carrinho/itens', ['product_id' => 999_999, 'quantity' => 1])
        ->assertSessionHasErrors('product_id');
    $this->post('/carrinho/itens', ['product_id' => $soldOut->id, 'quantity' => 1])
        ->assertSessionHasErrors(['quantity' => 'Este produto está indisponível no momento.']);
});

test('quantity must be between 1 and 999', function (int $quantity) {
    $product = Product::factory()->create();

    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => $quantity])->assertSessionHasErrors('quantity');
})->with([0, -1, 1000]);

test('quantities can be changed and items removed', function () {
    $product = Product::factory()->create(['manage_stock' => false]);
    $key = "{$product->id}-0";
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 1]);

    $this->patch("/carrinho/itens/{$key}", ['quantity' => 4])->assertRedirect();
    expect(session("cart.{$key}.quantity"))->toBe(4);

    $this->delete("/carrinho/itens/{$key}")->assertRedirect();
    expect(session('cart'))->toBe([]);
});

test('changing the quantity of an item that is not in the cart is not found', function () {
    $this->patch('/carrinho/itens/1-0', ['quantity' => 2])->assertNotFound();
});

test('more units than in stock block the checkout', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 3]);
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 5]);

    $this->get('/carrinho')->assertInertia(fn (Assert $page) => $page
        ->where('lines.0.enough_stock', false)
        ->where('lines.0.max_quantity', 3)
        ->where('canCheckout', false));
});

test('products deactivated after being added disappear from the cart', function () {
    $product = Product::factory()->create();
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 1]);

    $product->update(['is_active' => false]);

    $this->get('/carrinho')->assertInertia(fn (Assert $page) => $page->has('lines', 0)->where('canCheckout', false));
});

test('the header counter shows the number of units in the cart', function () {
    $product = Product::factory()->create(['manage_stock' => false]);
    $this->post('/carrinho/itens', ['product_id' => $product->id, 'quantity' => 3]);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('store.cart_count', 3));
});
