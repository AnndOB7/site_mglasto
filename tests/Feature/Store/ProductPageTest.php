<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

function diamondFloor(): Product
{
    $product = Product::factory()->create([
        'name' => 'PISO BORRACHA DIAMANTE',
        'slug' => 'piso-borracha-diamante',
        'sku' => 'PS-45151',
        'short_description' => 'Piso antiderrapante.',
        'description' => '<p style="x">Indicado para <strong>academias</strong>.</p><script>alert(1)</script>',
        'price' => 10_000,
        'sale_price' => 8_860,
        'manage_stock' => true,
        'stock_quantity' => 92,
    ]);

    $product->images()->createMany([
        ['path' => 'products/piso/1.jpg', 'alt' => 'Piso preto', 'position' => 0],
        ['path' => 'products/piso/2.jpg', 'alt' => null, 'position' => 1],
    ]);

    ProductVariant::factory()->for($product)->create(['name' => 'Preto / 3 mm', 'options' => ['Cor' => 'Preto', 'Espessura' => '3 mm'], 'price' => null, 'stock_quantity' => null]);
    ProductVariant::factory()->for($product)->create(['name' => 'Azul / 3 mm', 'options' => ['Cor' => 'Azul', 'Espessura' => '3 mm'], 'price' => 9_500, 'stock_quantity' => 0]);
    ProductVariant::factory()->for($product)->create(['name' => 'Verde / 3 mm', 'options' => ['Cor' => 'Verde', 'Espessura' => '3 mm'], 'is_active' => false]);

    return $product;
}

test('the product page shows photos, prices in cents and a sanitized description', function () {
    diamondFloor();

    $this->get('/produto/piso-borracha-diamante')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/product')
            ->where('product.name', 'PISO BORRACHA DIAMANTE')
            ->where('product.price', 10_000)
            ->where('product.sale_price', 8_860)
            ->where('product.in_stock', true)
            ->where('product.max_quantity', 92)
            ->where('product.images', [
                ['url' => '/storage/products/piso/1.jpg', 'alt' => 'Piso preto'],
                ['url' => '/storage/products/piso/2.jpg', 'alt' => 'PISO BORRACHA DIAMANTE'],
            ])
            ->where('product.description_html', '<p>Indicado para <strong>academias</strong>.</p>'));
});

test('only active variants are offered, with their own price and stock', function () {
    diamondFloor();

    $this->get('/produto/piso-borracha-diamante')->assertInertia(fn (Assert $page) => $page
        ->has('product.variants', 2)
        ->where('product.variants.0.name', 'Azul / 3 mm')
        ->where('product.variants.0.price', 9_500)
        ->where('product.variants.0.in_stock', false)
        ->where('product.variants.1.name', 'Preto / 3 mm')
        ->where('product.variants.1.price', null)
        ->where('product.variants.1.in_stock', true)
        ->where('product.option_groups', [
            ['name' => 'Cor', 'values' => ['Azul', 'Preto']],
            ['name' => 'Espessura', 'values' => ['3 mm']],
        ]));
});

test('inactive or deleted products are not found', function () {
    Product::factory()->create(['slug' => 'inativo', 'is_active' => false]);
    Product::factory()->create(['slug' => 'apagado'])->delete();

    $this->get('/produto/inativo')->assertNotFound();
    $this->get('/produto/apagado')->assertNotFound();
});

test('related products come from the same categories, without the product itself', function () {
    $category = Category::factory()->create(['slug' => 'pisos']);
    $product = diamondFloor();
    $category->products()->attach([
        $product->id,
        Product::factory()->create(['name' => 'Piso moeda'])->id,
        Product::factory()->create(['name' => 'Piso inativo', 'is_active' => false])->id,
    ]);
    Product::factory()->create(['name' => 'Fora da categoria']);

    $this->get('/produto/piso-borracha-diamante')->assertInertia(fn (Assert $page) => $page
        ->has('related', 1)
        ->where('related.0.name', 'Piso moeda')
        ->where('category.url', '/categoria/pisos'));
});

test('the WhatsApp button links to the configured number with the product', function () {
    config(['mglasto.whatsapp_number' => '+55 (11) 99999-0000']);
    diamondFloor();

    $this->get('/produto/piso-borracha-diamante')->assertInertia(fn (Assert $page) => $page
        ->where('whatsappUrl', fn (string $url) => str_starts_with($url, 'https://wa.me/5511999990000?text=')
            && str_contains(rawurldecode($url), 'PISO BORRACHA DIAMANTE')));
});

test('without a WhatsApp number there is no link to a made-up number', function () {
    config(['mglasto.whatsapp_number' => null]);
    diamondFloor();

    $this->get('/produto/piso-borracha-diamante')->assertInertia(fn (Assert $page) => $page->where('whatsappUrl', null));
});

test('link preview tags are in the html sent by the server', function () {
    diamondFloor();

    $this->get('/produto/piso-borracha-diamante')
        ->assertSee('<meta property="og:title" content="PISO BORRACHA DIAMANTE">', false)
        ->assertSee('<meta name="description" content="Piso antiderrapante.">', false)
        ->assertSee('<meta property="og:image" content="'.url('/storage/products/piso/1.jpg').'">', false)
        ->assertSee('<title>PISO BORRACHA DIAMANTE - ', false);
});

test('the breadcrumb prefers a real category over the "Oferta" showcase', function () {
    $product = diamondFloor();
    $offer = Category::factory()->create(['name' => 'Oferta', 'slug' => 'oferta', 'position' => 0]);
    $floors = Category::factory()->create(['name' => 'Pisos', 'slug' => 'pisos', 'position' => 5]);
    $product->categories()->attach([$offer->id, $floors->id]);

    $this->get('/produto/piso-borracha-diamante')->assertInertia(fn (Assert $page) => $page->where('category.name', 'Pisos'));
});
