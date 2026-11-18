<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Store\CategoryController;
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
