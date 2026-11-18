<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Minimal copies of what the real Store API returns.
 */
function wooPrices(int $regular, ?int $sale = null): array
{
    return [
        'price' => (string) ($sale ?? $regular),
        'regular_price' => (string) $regular,
        'sale_price' => (string) ($sale ?? $regular),
        'currency_minor_unit' => 2,
    ];
}

function fakeWooStore(): void
{
    $testeira = [
        'id' => 450, 'type' => 'simple', 'name' => 'TESTEIRA BOR 3X25X65MM', 'slug' => 'testeira',
        'sku' => 'TESTEIRA', 'short_description' => '', 'on_sale' => false,
        'description' => '<p style="color:red" onclick="x()">Indicada para <strong>proteção</strong>.</p><script>alert(1)</script>',
        'prices' => wooPrices(1900), 'is_in_stock' => true, 'stock_availability' => ['text' => ''],
        'categories' => [['id' => 24, 'slug' => 'pisos-e-revestimentos']],
        'images' => [['id' => 451, 'src' => 'https://loja.test/uploads/testeira.png', 'alt' => '']],
        'attributes' => [], 'variations' => [],
    ];

    // Like the real store, "Oferta" is missing from the product's own category list.
    $piso = [
        'id' => 371, 'type' => 'variable', 'name' => 'PISO BORRACHA DIAMANTE', 'slug' => 'piso-borracha-diamante',
        'sku' => 'PS-45151', 'short_description' => '<p>Piso antiderrapante.</p>', 'on_sale' => true,
        'description' => '', 'prices' => wooPrices(10000, 8860),
        'is_in_stock' => true, 'stock_availability' => ['text' => '92 em estoque'],
        'categories' => [['id' => 24, 'slug' => 'pisos-e-revestimentos']],
        'images' => [],
        'attributes' => [
            ['name' => 'Cor', 'terms' => [['slug' => 'preto', 'name' => 'Preto'], ['slug' => 'azul', 'name' => 'Azul']]],
            ['name' => 'Espessura', 'terms' => [['slug' => '3-mm', 'name' => '3 mm']]],
        ],
        'variations' => [
            ['id' => 392, 'attributes' => [['name' => 'Cor', 'value' => 'preto'], ['name' => 'Espessura', 'value' => '3-mm']]],
            ['id' => 393, 'attributes' => [['name' => 'Cor', 'value' => 'azul'], ['name' => 'Espessura', 'value' => '3-mm']]],
        ],
    ];

    Http::fake(function (Request $request) use ($testeira, $piso) {
        $path = parse_url($request->url(), PHP_URL_PATH);
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return match (true) {
            str_starts_with($path, '/uploads/') => Http::response('fake-image-bytes'),
            str_ends_with($path, '/products/categories') => Http::response([
                ['id' => 24, 'name' => 'Pisos e Revestimentos', 'slug' => 'pisos-e-revestimentos', 'description' => '', 'parent' => 0],
                ['id' => 30, 'name' => 'Oferta', 'slug' => 'oferta', 'description' => '', 'parent' => 0],
            ]),
            str_ends_with($path, '/products/392') => Http::response([
                'id' => 392, 'sku' => 'PS-45151', 'prices' => wooPrices(8860),
                'is_in_stock' => true, 'stock_availability' => ['text' => '92 em estoque'],
            ]),
            str_ends_with($path, '/products/393') => Http::response([
                'id' => 393, 'sku' => 'PS-45151', 'prices' => wooPrices(9500),
                'is_in_stock' => false, 'stock_availability' => ['text' => 'Fora de estoque'],
            ]),
            str_ends_with($path, '/products') => Http::response(match ($query['category'] ?? null) {
                '24' => [$testeira, $piso],
                '30' => [$piso],
                null => [$testeira, $piso],
                default => [],
            }),
            default => Http::response(null, 404),
        };
    });
}

beforeEach(function () {
    Storage::fake('public');
    fakeWooStore();
});

test('it imports categories, products, variants and images', function () {
    $this->artisan('mglasto:importar-woocommerce', ['--url' => 'https://loja.test'])->assertSuccessful();

    expect(Category::count())->toBe(2)
        ->and(Product::count())->toBe(2)
        ->and(ProductVariant::count())->toBe(2);

    $piso = Product::where('woo_id', 371)->first();

    expect($piso->price)->toBe(10000)
        ->and($piso->sale_price)->toBe(8860)
        ->and($piso->stock_quantity)->toBe(92)
        ->and($piso->is_featured)->toBeTrue() // found only through the "Oferta" category query
        ->and($piso->short_description)->toBe('Piso antiderrapante.')
        ->and($piso->categories->pluck('slug')->sort()->values()->all())->toBe(['oferta', 'pisos-e-revestimentos']);

    $testeira = Product::where('woo_id', 450)->first();

    expect($testeira->images)->toHaveCount(1)
        ->and($testeira->is_featured)->toBeFalse()
        ->and($testeira->manage_stock)->toBeFalse();

    Storage::disk('public')->assertExists($testeira->images->first()->path);
});

test('variants get readable names and do not copy the parent sku or stock', function () {
    $this->artisan('mglasto:importar-woocommerce', ['--url' => 'https://loja.test'])->assertSuccessful();

    $preto = ProductVariant::where('woo_id', 392)->first();
    $azul = ProductVariant::where('woo_id', 393)->first();

    expect($preto->name)->toBe('Preto / 3 mm')
        ->and($preto->options)->toBe(['Cor' => 'Preto', 'Espessura' => '3 mm'])
        ->and($preto->sku)->toBeNull()
        ->and($preto->price)->toBeNull()
        ->and($preto->stock_quantity)->toBeNull()
        ->and($azul->price)->toBe(9500)
        ->and($azul->stock_quantity)->toBe(0)
        ->and($azul->is_active)->toBeFalse();
});

test('descriptions keep simple formatting but lose scripts and attributes', function () {
    $this->artisan('mglasto:importar-woocommerce', ['--url' => 'https://loja.test'])->assertSuccessful();

    expect(Product::where('woo_id', 450)->value('description'))
        ->toBe('<p>Indicada para <strong>proteção</strong>.</p>alert(1)');
});

test('running the import twice does not duplicate anything', function () {
    $this->artisan('mglasto:importar-woocommerce', ['--url' => 'https://loja.test'])->assertSuccessful();
    $this->artisan('mglasto:importar-woocommerce', ['--url' => 'https://loja.test'])->assertSuccessful();

    expect(Category::count())->toBe(2)
        ->and(Product::count())->toBe(2)
        ->and(ProductVariant::count())->toBe(2)
        ->and(Product::where('woo_id', 450)->first()->images)->toHaveCount(1);
});

test('the import can skip images', function () {
    $this->artisan('mglasto:importar-woocommerce', ['--url' => 'https://loja.test', '--sem-imagens' => true])
        ->assertSuccessful();

    expect(Product::where('woo_id', 450)->first()->images)->toHaveCount(0);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/uploads/'));
});
