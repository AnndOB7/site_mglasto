<?php

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;

test('a product can belong to several categories', function () {
    $product = Product::factory()->create();
    $categories = Category::factory()->count(2)->create();

    $product->categories()->attach($categories);

    expect($product->categories)->toHaveCount(2)
        ->and($categories->first()->products->first()->is($product))->toBeTrue();
});

test('the current price uses the sale price when there is one', function () {
    $product = Product::factory()->create(['price' => 10_000, 'sale_price' => 8_860]);

    expect($product->currentPrice())->toBe(8_860);
});

test('a variant without its own price uses the product price', function () {
    $variant = ProductVariant::factory()
        ->for(Product::factory()->state(['price' => 8_860]))
        ->create(['price' => null]);

    expect($variant->currentPrice())->toBe(8_860)
        ->and($variant->options)->toHaveKey('Espessura');
});

test('stock is only checked when the product manages stock', function () {
    expect(Product::factory()->outOfStock()->create()->isInStock())->toBeFalse()
        ->and(Product::factory()->outOfStock()->create(['manage_stock' => false])->isInStock())->toBeTrue();
});

test('orders cast channel and status to enums', function () {
    $order = Order::factory()->fromChannel(OrderChannel::Shopee)->create();

    expect($order->fresh()->channel)->toBe(OrderChannel::Shopee)
        ->and($order->status)->toBe(OrderStatus::Quote)
        ->and($order->status->label())->toBe('Orçamento');
});

test('the same marketplace order cannot be imported twice', function () {
    Order::factory()->create(['channel' => OrderChannel::MercadoLivre, 'external_id' => '123']);

    Order::factory()->create(['channel' => OrderChannel::MercadoLivre, 'external_id' => '123']);
})->throws(QueryException::class);

test('order items keep the product name after the product is deleted', function () {
    $product = Product::factory()->create(['name' => 'Piso Borracha Diamante']);
    $order = Order::factory()->create();

    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => 8_860,
        'quantity' => 2,
        'total' => 17_720,
    ]);

    $product->forceDelete();

    expect($item->fresh()->product_id)->toBeNull()
        ->and($item->fresh()->product_name)->toBe('Piso Borracha Diamante');
});
