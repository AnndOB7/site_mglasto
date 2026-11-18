<?php

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function customer(array $overrides = []): array
{
    return [
        'name' => 'Maria da Silva',
        'phone' => '(11) 99999-0000',
        'email' => 'maria@example.com',
        'document' => '529.982.247-25',
        'zip' => '01001-000',
        'street' => 'Praça da Sé',
        'number' => '100',
        'complement' => 'Sala 2',
        'district' => 'Sé',
        'city' => 'São Paulo',
        'state' => 'sp',
        'notes' => 'Entregar pela manhã',
        ...$overrides,
    ];
}

function cartWithDiamondFloor($test): array
{
    $product = Product::factory()->create(['name' => 'PISO BORRACHA DIAMANTE', 'sku' => 'PS-45151', 'price' => 10_000, 'sale_price' => 8_860, 'manage_stock' => true, 'stock_quantity' => 92]);
    $variant = ProductVariant::factory()->for($product)->create(['name' => 'Preto / 3 mm', 'sku' => null, 'price' => null, 'stock_quantity' => null]);

    $test->post('/carrinho/itens', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2]);

    return [$product, $variant];
}

test('the checkout page needs something in the cart', function () {
    $this->get('/orcamento')->assertRedirect('/carrinho');
});

test('placing a quote creates the order and its items with copies, and clears the cart', function () {
    [$product, $variant] = cartWithDiamondFloor($this);

    // Extra fields trying to change the total are ignored.
    $response = $this->post('/orcamento', customer(['total' => 1, 'subtotal' => 1, 'status' => 'delivered']));

    $order = Order::sole();
    $response->assertRedirect("/orcamento/{$order->number}/enviado");

    expect($order)
        ->number->toBe(sprintf('MGL-%06d', $order->id))
        ->channel->toBe(OrderChannel::Site)
        ->status->toBe(OrderStatus::Quote)
        ->subtotal->toBe(17_720)
        ->total->toBe(17_720)
        ->customer_phone->toBe('11999990000')
        ->customer_document->toBe('52998224725')
        ->shipping_zip->toBe('01001000')
        ->shipping_state->toBe('SP');

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => 'PISO BORRACHA DIAMANTE',
        'variant_name' => 'Preto / 3 mm',
        'sku' => 'PS-45151',
        'unit_price' => 8_860,
        'quantity' => 2,
        'total' => 17_720,
    ]);

    expect(session('cart'))->toBeNull();
});

test('order items keep their copy after the product changes', function () {
    [$product] = cartWithDiamondFloor($this);
    $this->post('/orcamento', customer());

    $product->update(['name' => 'Nome novo', 'sale_price' => 1_000]);
    $product->delete();

    $this->assertDatabaseHas('order_items', ['product_name' => 'PISO BORRACHA DIAMANTE', 'unit_price' => 8_860]);
});

test('required fields and formats are validated with messages in portuguese', function () {
    cartWithDiamondFloor($this);

    $this->post('/orcamento', [])->assertSessionHasErrors([
        'name' => 'Informe seu nome.',
        'phone' => 'Informe um telefone para contato.',
        'zip' => 'Informe o CEP.',
        'street' => 'Informe a rua.',
        'number' => 'Informe o número (ou "s/n").',
        'district' => 'Informe o bairro.',
        'city' => 'Informe a cidade.',
        'state' => 'Informe o estado (UF).',
    ]);

    $this->assertDatabaseCount('orders', 0);
});

test('invalid phone, document, zip and state are rejected', function (string $field, string $value, string $message) {
    cartWithDiamondFloor($this);

    $this->post('/orcamento', customer([$field => $value]))->assertSessionHasErrors([$field => $message]);
    $this->assertDatabaseCount('orders', 0);
})->with([
    'phone without area code' => ['phone', '9999-0000', 'Informe o telefone com DDD, por exemplo (11) 99999-0000.'],
    'invalid CPF' => ['document', '529.982.247-26', 'Informe um CPF ou CNPJ válido.'],
    'short zip' => ['zip', '0100', 'O CEP tem 8 números.'],
    'unknown state' => ['state', 'XX', 'Escolha um estado válido.'],
]);

test('email and document are optional', function () {
    cartWithDiamondFloor($this);

    $this->post('/orcamento', customer(['email' => '', 'document' => '']))->assertSessionHasNoErrors();
    expect(Order::sole())->customer_email->toBeNull()->customer_document->toBeNull();
});

test('an order is not placed when stock ran out after adding to the cart', function () {
    [$product] = cartWithDiamondFloor($this);
    $product->update(['stock_quantity' => 1]);

    $this->post('/orcamento', customer())->assertSessionHasErrors('cart');
    $this->assertDatabaseCount('orders', 0);
    expect(session('cart'))->not->toBeEmpty();
});

test('the confirmation page shows the order and a WhatsApp link with its summary', function () {
    config(['mglasto.whatsapp_number' => '5511999990000']);
    cartWithDiamondFloor($this);
    $this->post('/orcamento', customer());
    $order = Order::sole();

    $this->get("/orcamento/{$order->number}/enviado")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/checkout-done')
            ->where('order.number', $order->number)
            ->where('order.total', 17_720)
            ->where('whatsappUrl', function (string $url) use ($order) {
                $text = rawurldecode($url);

                return str_starts_with($url, 'https://wa.me/5511999990000?text=')
                    && str_contains($text, $order->number)
                    && str_contains($text, '2x PISO BORRACHA DIAMANTE (Preto / 3 mm)')
                    && str_contains($text, 'R$ 177,20')
                    && str_contains($text, 'São Paulo/SP, CEP 01001-000');
            }));
});

test('without a WhatsApp number the confirmation has no link', function () {
    config(['mglasto.whatsapp_number' => '']);
    cartWithDiamondFloor($this);
    $this->post('/orcamento', customer());

    $this->get('/orcamento/'.Order::sole()->number.'/enviado')
        ->assertInertia(fn (Assert $page) => $page->where('whatsappUrl', null));
});

test('another visitor cannot open the confirmation of someone else\'s order', function () {
    $order = Order::factory()->create(['number' => 'MGL-000001']);

    $this->get('/orcamento/MGL-000001/enviado')->assertNotFound();
});

test('quote submissions are rate limited', function () {
    cartWithDiamondFloor($this);

    for ($i = 0; $i < 10; $i++) {
        $this->post('/orcamento', []);
    }

    $this->post('/orcamento', [])->assertTooManyRequests();
});

test('the placed order shows up in the admin order list', function () {
    config(['mglasto.admin_emails' => ['admin@mglasto.test']]);
    cartWithDiamondFloor($this);
    $this->post('/orcamento', customer());
    $order = Order::sole();

    $this->actingAs(User::factory()->create(['email' => 'admin@mglasto.test']))
        ->get('/admin/pedidos')
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee('Maria da Silva')
        ->assertSee('Orçamento');
});
