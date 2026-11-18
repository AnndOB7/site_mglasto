<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\SearchController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

/*
| Loja (páginas públicas). Endereços em português, no mesmo padrão do site
| atual (/produto/...), o que facilita redirecionamentos no futuro.
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/categoria/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/produto/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/busca', SearchController::class)->name('search');

// Orçamento (carrinho na sessão, sem login).
Route::get('/carrinho', [CartController::class, 'show'])->name('cart.show');
Route::post('/carrinho/itens', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/carrinho/itens/{key}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/carrinho/itens/{key}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::get('/orcamento', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/orcamento', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/orcamento/{order:number}/enviado', [CheckoutController::class, 'done'])->name('checkout.done');

if (! app()->isProduction()) {
    Route::inertia('/guia-visual', 'store/style-guide')->name('style-guide');
}

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
