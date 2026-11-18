<?php

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['mglasto.admin_emails' => ['admin@mglasto.test']]);
});

function admin(): User
{
    return User::factory()->create(['email' => 'admin@mglasto.test']);
}

test('guests are sent to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('users whose e-mail is not in ADMIN_EMAILS get 403', function () {
    $this->actingAs(User::factory()->create(['email' => 'cliente@example.com']))
        ->get('/admin')
        ->assertForbidden();
});

test('admins can open every admin page', function (string $url) {
    Product::factory()->create();
    Order::factory()->create();

    $this->actingAs(admin())->get($url)->assertOk();
})->with([
    '/admin',
    '/admin/categorias',
    '/admin/produtos',
    '/admin/produtos/create',
    '/admin/pedidos',
]);

test('the admin e-mail check ignores letter case', function () {
    config(['mglasto.admin_emails' => ['admin@mglasto.test']]);

    $this->actingAs(User::factory()->create(['email' => 'Admin@MGLasto.test']))
        ->get('/admin')
        ->assertOk();
});

test('prices typed in reais are saved in cents', function () {
    $this->actingAs(admin());

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Piso de teste',
            'slug' => 'piso-de-teste',
            'price' => '1.088,60',
            'sale_price' => '88,60',
            'manage_stock' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', ['slug' => 'piso-de-teste', 'price' => 108_860, 'sale_price' => 8_860]);
});

test('saved prices are shown in reais when editing', function () {
    $this->actingAs(admin());
    $product = Product::factory()->create(['price' => 8_860, 'sale_price' => null]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertSchemaStateSet(['price' => '88,60', 'sale_price' => null]);
});

test('an invalid price shows a message instead of saving', function () {
    $this->actingAs(admin());

    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Piso', 'slug' => 'piso', 'price' => '88,605'])
        ->call('create')
        ->assertHasFormErrors(['price']);

    $this->assertDatabaseMissing('products', ['slug' => 'piso']);
});

test('the sale price must be lower than the regular price', function () {
    $this->actingAs(admin());

    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Piso', 'slug' => 'piso', 'price' => '88,60', 'sale_price' => '90,00'])
        ->call('create')
        ->assertHasFormErrors(['sale_price']);
});

test('only the order status can be changed in the panel', function () {
    $this->actingAs(admin());
    $order = Order::factory()->create(['status' => OrderStatus::Quote, 'customer_name' => 'Maria', 'total' => 8_860]);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm(['status' => OrderStatus::Confirmed->value, 'customer_name' => 'Alterado', 'total' => 1])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($order->fresh())
        ->status->toBe(OrderStatus::Confirmed)
        ->customer_name->toBe('Maria')
        ->total->toBe(8_860);
});

test('orders cannot be created by hand in the panel', function () {
    $this->actingAs(admin());

    $this->get('/admin/pedidos/create')->assertNotFound();
});
