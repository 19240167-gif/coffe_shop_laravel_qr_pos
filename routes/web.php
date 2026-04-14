<?php

use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/dashboard');

Route::get('/table/{token}', [CustomerOrderController::class, 'show'])->name('customer.table');
Route::post('/table/{token}/orders', [CustomerOrderController::class, 'store'])->name('customer.orders.store');

Route::prefix('dashboard')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::post('/menu-items', [DashboardController::class, 'storeMenuItem'])->name('dashboard.menu-items.store');
    Route::post('/table-seats', [DashboardController::class, 'storeTable'])->name('dashboard.table-seats.store');
    Route::post('/orders/{order}/status', [DashboardController::class, 'updateOrderStatus'])->name('dashboard.orders.status');
    Route::post('/menu-items/{menuItem}/stock', [DashboardController::class, 'adjustStock'])->name('dashboard.menu-items.stock');
});
