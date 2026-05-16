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

        Route::get('/erp', fn () => 'ERP Dashboard')->name('erp.dashboard');

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', fn () => 'Orders Index')->name('index');
            Route::get('/create', fn () => 'Orders Create')->name('create');
            Route::post('/', fn () => 'Orders Store')->name('store');
            Route::get('/{order}', fn () => 'Orders Show')->name('show');
            Route::get('/{order}/edit', fn () => 'Orders Edit')->name('edit');
            Route::put('/{order}', fn () => 'Orders Update')->name('update');
            Route::delete('/{order}', fn () => 'Orders Destroy')->name('destroy');
        });

        Route::prefix('distribution')->name('distribution.')->group(function () {
            Route::get('/shipments', fn () => 'Shipments Index')->name('shipments.index');
            Route::get('/shipments/create', fn () => 'Shipments Create')->name('shipments.create');
            Route::post('/shipments', fn () => 'Shipments Store')->name('shipments.store');
            Route::get('/shipments/{shipment}', fn () => 'Shipments Show')->name('shipments.show');
        });

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', fn () => 'Invoices Index')->name('index');
            Route::get('/create', fn () => 'Invoices Create')->name('create');
            Route::post('/', fn () => 'Invoices Store')->name('store');
            Route::get('/{invoice}', fn () => 'Invoices Show')->name('show');
            Route::get('/{invoice}/edit', fn () => 'Invoices Edit')->name('edit');
            Route::put('/{invoice}', fn () => 'Invoices Update')->name('update');
            Route::delete('/{invoice}', fn () => 'Invoices Destroy')->name('destroy');
        });

        Route::prefix('erp')->name('erp.')
            ->middleware('role:accountant|super_admin')
            ->group(function () {
                Route::get('/expenses', fn () => 'Expenses Index')->name('expenses.index');
                Route::get('/reports', fn () => 'Reports Index')->name('reports.index');
            });

        Route::prefix('admin')->name('admin.')
            ->middleware('role:super_admin')
            ->group(function () {
                Route::get('/users', fn () => 'Users Index')->name('users.index');
                Route::get('/settings', fn () => 'Settings Index')->name('settings.index');
            });
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
