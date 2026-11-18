<?php

use App\Models\Category;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config([
        'mglasto.whatsapp_number' => null,
        'mglasto.company' => ['name' => 'MGLASTO', 'phone' => null, 'email' => null, 'address' => null, 'document' => null],
    ]);
});

test('each institutional page loads with its title', function (string $url, string $title) {
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/institutional')
            ->where('title', $title)
            ->has('sections'));
})->with([
    ['/atendimento', 'Atendimento'],
    ['/perguntas-frequentes', 'Perguntas frequentes'],
    ['/trocas-e-devolucoes', 'Trocas e devoluções'],
]);

test('policies that MGLASTO has not defined stay marked instead of invented', function () {
    $this->get('/trocas-e-devolucoes')->assertInertia(fn (Assert $page) => $page
        ->where('sections', fn ($sections) => collect($sections)->every(
            fn (array $section) => str_starts_with($section['body'], '[A DEFINIR]')
        )));
});

test('missing contact details show [A DEFINIR] and there is no WhatsApp link', function () {
    $this->get('/atendimento')->assertInertia(fn (Assert $page) => $page
        ->where('sections.0.body', '[A DEFINIR]: número do WhatsApp')
        ->where('sections.1.body', '[A DEFINIR]')
        ->where('whatsappUrl', null));
});

test('configured contact details are shown', function () {
    config([
        'mglasto.whatsapp_number' => '5511900000000',
        'mglasto.company.phone' => '(11) 3333-0000',
    ]);

    $this->get('/atendimento')->assertInertia(fn (Assert $page) => $page
        ->where('sections.0.body', '+5511900000000')
        ->where('sections.1.body', '(11) 3333-0000')
        ->where('whatsappUrl', fn (string $url) => str_starts_with($url, 'https://wa.me/5511900000000')));
});

test('every link in the store header, menu and footer opens a real page', function () {
    Category::factory()->create(['slug' => 'pisos']);

    $store = null;
    $this->get('/')->assertInertia(function (Assert $page) use (&$store) {
        $store = $page->toArray()['props']['store'];
    });

    $urls = [
        $store['home_url'],
        $store['search_url'],
        $store['cart_url'],
        ...array_column($store['categories'], 'url'),
        ...array_column($store['institutional'], 'url'),
    ];

    expect($urls)->each->not->toBe('#');

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }
});
