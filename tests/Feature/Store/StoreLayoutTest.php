<?php

use App\Models\Category;
use Inertia\Testing\AssertableInertia as Assert;

test('the store menu lists only active top-level categories in order', function () {
    $second = Category::factory()->create(['name' => 'Segunda', 'slug' => 'segunda', 'position' => 2]);
    $first = Category::factory()->create(['name' => 'Primeira', 'slug' => 'primeira', 'position' => 1]);
    Category::factory()->create(['slug' => 'inativa', 'is_active' => false]);
    Category::factory()->create(['slug' => 'filha', 'parent_id' => $first->id]);

    $this->get('/guia-visual')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('store/style-guide')
            ->where('store.categories', [
                ['name' => 'Primeira', 'slug' => 'primeira', 'url' => '/categoria/primeira'],
                ['name' => 'Segunda', 'slug' => 'segunda', 'url' => '/categoria/segunda'],
            ]));
});

test('company details that are not configured stay empty instead of invented', function () {
    config(['mglasto.company' => ['name' => 'MGLASTO', 'phone' => null, 'email' => null, 'address' => null, 'document' => null]]);

    $this->get('/guia-visual')
        ->assertInertia(fn (Assert $page) => $page
            ->where('store.company.phone', null)
            ->where('store.company.document', null));
});

test('the cart counter adds up the quantities in the session', function () {
    $this->withSession(['cart' => [
        'a' => ['quantity' => 2],
        'b' => ['quantity' => 3],
    ]])
        ->get('/guia-visual')
        ->assertInertia(fn (Assert $page) => $page->where('store.cart_count', 5));
});
