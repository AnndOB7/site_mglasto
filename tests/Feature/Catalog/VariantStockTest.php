<?php

use App\Models\Product;
use App\Models\ProductVariant;

function variantOf(array $product, array $variant): ProductVariant
{
    return ProductVariant::factory()->for(Product::factory()->state($product))->create($variant);
}

test('a variant without its own stock shares the product stock', function (int $productStock, bool $expected) {
    expect(variantOf(['manage_stock' => true, 'stock_quantity' => $productStock], ['stock_quantity' => null])->isInStock())
        ->toBe($expected);
})->with([
    'product has stock' => [92, true],
    'product sold out' => [0, false],
]);

test('a variant with its own stock uses it', function (int $variantStock, bool $expected) {
    expect(variantOf(['manage_stock' => true, 'stock_quantity' => 92], ['stock_quantity' => $variantStock])->isInStock())
        ->toBe($expected);
})->with([
    'variant has stock' => [3, true],
    'variant sold out' => [0, false],
]);

test('without stock control the variant is always available', function () {
    expect(variantOf(['manage_stock' => false, 'stock_quantity' => 0], ['stock_quantity' => 0])->isInStock())->toBeTrue();
});

test('an inactive variant is never available', function () {
    expect(variantOf(['manage_stock' => false], ['is_active' => false])->isInStock())->toBeFalse();
});
