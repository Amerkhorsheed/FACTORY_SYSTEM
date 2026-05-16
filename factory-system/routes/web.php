<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(route('login')));

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Admin & Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'portal', 'role:super_admin|accountant|shipping_staff'])
    ->group(function () {
        Route::get('/dashboard', fn () => redirect(route('erp.dashboard')))->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| Customer Portal Routes
|--------------------------------------------------------------------------
*/

Route::prefix('portal')->name('portal.')
    ->middleware(['auth', 'active', 'portal'])
    ->group(function () {
        Route::get('/dashboard', fn () => 'Portal Dashboard')->name('dashboard');
        Route::get('/orders', fn () => 'Portal Orders')->name('orders');
        Route::get('/invoices', fn () => 'Portal Invoices')->name('invoices');
    });

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

require __DIR__.'/products.php';
require __DIR__.'/customers.php';
require __DIR__.'/orders.php';
require __DIR__.'/shipments.php';
require __DIR__.'/invoices.php';
require __DIR__.'/payments.php';
require __DIR__.'/erp.php';
require __DIR__.'/admin.php';
