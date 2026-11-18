<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function productNamed(string $name, array $attributes = []): Product
{
    return Product::factory()->create(['name' => $name, 'slug' => str($name)->slug()->toString(), ...$attributes]);
}

test('the home shows featured products and never repeats them in the other showcase', function () {
    $offer = productNamed('Piso em oferta', ['is_featured' => true]);
    $regular = productNamed('Tapete comum');
    productNamed('Produto escondido', ['is_active' => false, 'is_featured' => true]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/home')
            ->where('featured.0.name', $offer->name)
            ->has('featured', 1)
            ->where('products.0.name', $regular->name)
            ->has('products', 1)
            ->where('productsTotal', 2));
});

test('the home lists only active categories that have active products', function () {
    $withProducts = Category::factory()->create(['name' => 'Pisos', 'slug' => 'pisos']);
    $withProducts->products()->attach(productNamed('Piso'));
    Category::factory()->create(['slug' => 'vazia']);
    Category::factory()->inactive()->create(['slug' => 'inativa'])->products()->attach(productNamed('Outro'));

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->has('categories', 1)
        ->where('categories.0.name', 'Pisos')
        ->where('categories.0.url', '/categoria/pisos')
        ->where('categories.0.products_count', 1));
});

test('a category page lists its active products and those of its subcategories', function () {
    $category = Category::factory()->create(['slug' => 'pisos']);
    $child = Category::factory()->create(['slug' => 'pisos-de-borracha', 'parent_id' => $category->id]);
    $category->products()->attach([productNamed('Piso A')->id, productNamed('Piso inativo', ['is_active' => false])->id]);
    $child->products()->attach(productNamed('Piso B'));
    productNamed('Fora da categoria');

    $this->get('/categoria/pisos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/category')
            ->where('products.total', 2)
            ->where('products.data.0.name', 'Piso A')
            ->where('products.data.1.name', 'Piso B')
            ->where('subcategories.0.url', '/categoria/pisos-de-borracha'));
});

test('inactive or unknown categories are not found', function () {
    Category::factory()->inactive()->create(['slug' => 'antiga']);

    $this->get('/categoria/antiga')->assertNotFound();
    $this->get('/categoria/nao-existe')->assertNotFound();
});

test('sorting by price uses the price the customer pays', function (string $sort, array $expected) {
    $category = Category::factory()->create(['slug' => 'pisos']);
    $category->products()->attach([
        productNamed('Caro em promocao', ['price' => 50_000, 'sale_price' => 1_000])->id,
        productNamed('Medio', ['price' => 5_000])->id,
        productNamed('Barato', ['price' => 2_000])->id,
    ]);

    $this->get("/categoria/pisos?ordem={$sort}")->assertInertia(fn (Assert $page) => $page
        ->where('sort', $sort)
        ->where('products.data.0.name', $expected[0])
        ->where('products.data.1.name', $expected[1])
        ->where('products.data.2.name', $expected[2]));
})->with([
    'lowest price' => ['menor-preco', ['Caro em promocao', 'Barato', 'Medio']],
    'highest price' => ['maior-preco', ['Medio', 'Barato', 'Caro em promocao']],
    'name' => ['nome', ['Barato', 'Caro em promocao', 'Medio']],
]);

test('an unknown sort falls back to name', function () {
    $this->get('/busca?ordem=qualquer-coisa')->assertInertia(fn (Assert $page) => $page->where('sort', 'nome'));
});

test('search finds products by name, by sku and by variant sku', function (string $term) {
    $piso = productNamed('PISO BORRACHA DIAMANTE', ['sku' => 'PS-45151']);
    ProductVariant::factory()->for($piso)->create(['sku' => 'PS-45151-AZ']);
    productNamed('TAPETE VAZADO', ['sku' => 'TP-100']);

    $this->get('/busca?q='.urlencode($term))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/search')
            ->where('term', $term)
            ->where('products.total', 1)
            ->where('products.data.0.name', 'PISO BORRACHA DIAMANTE'));
})->with([
    'part of the name' => ['diamante'],
    'words in any order' => ['diamante piso'],
    'sku' => ['ps-45151'],
    'variant sku' => ['45151-AZ'],
]);

test('search hides inactive products', function () {
    productNamed('Piso ativo');
    productNamed('Piso inativo', ['is_active' => false]);

    $this->get('/busca?q=piso')->assertInertia(fn (Assert $page) => $page
        ->where('products.total', 1)
        ->where('products.data.0.name', 'Piso ativo'));
});

test('% and _ typed in the search do not work as SQL wildcards', function (string $typed) {
    productNamed('Lencol');

    // As wildcards, "L%l" and "L_ncol" would match "Lencol".
    $this->get('/busca?q='.urlencode($typed))->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
})->with(['L%l', 'L_ncol']);

test('search without a term lists every active product', function () {
    productNamed('Um');
    productNamed('Dois');

    $this->get('/busca')->assertInertia(fn (Assert $page) => $page->where('term', '')->where('products.total', 2));
});

test('product cards carry the photo, the product url and prices in cents', function () {
    $product = productNamed('Piso', ['price' => 10_000, 'sale_price' => 8_860]);
    ProductImage::create(['product_id' => $product->id, 'path' => 'products/piso/1.jpg', 'alt' => 'Piso preto', 'position' => 0]);

    $this->get('/busca')->assertInertia(fn (Assert $page) => $page
        ->where('products.data.0.url', '/produto/piso')
        ->where('products.data.0.price', 10_000)
        ->where('products.data.0.sale_price', 8_860)
        ->where('products.data.0.image', ['url' => '/storage/products/piso/1.jpg', 'alt' => 'Piso preto']));
});

test('listing pages run the same number of queries for 3 or 30 products', function () {
    $category = Category::factory()->create(['slug' => 'pisos']);

    $countQueries = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/categoria/pisos')->assertOk();
        $this->get('/')->assertOk();

        return count(DB::getQueryLog());
    };

    $addProducts = fn (int $count) => Product::factory()->count($count)->create()
        ->each(function (Product $product) use ($category) {
            $category->products()->attach($product);
            $product->images()->createMany([
                ['path' => "p/{$product->id}-1.jpg", 'position' => 0],
                ['path' => "p/{$product->id}-2.jpg", 'position' => 1],
            ]);
        });

    $addProducts(3);
    $few = $countQueries();

    $addProducts(27);
    $many = $countQueries();

    expect($many)->toBe($few);
});
