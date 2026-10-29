<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyStockController;
use App\Http\Controllers\DisplayStockController;
use App\Http\Controllers\MainStockController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SisaDisplayController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockHistoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::middleware('admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Stock
    Route::get('/main-stocks', [MainStockController::class, 'index'])->name('main-stocks.index');
    Route::post('/main-stocks', [MainStockController::class, 'store'])->name('main-stocks.store');

    Route::get('/display-stocks', [DisplayStockController::class, 'index'])->name('display-stocks.index');
    Route::post('/display-stocks', [DisplayStockController::class, 'store'])->name('display-stocks.store');

    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');

    Route::get('/stock-outs', [StockOutController::class, 'index'])->name('stock-outs.index');
    Route::post('/stock-outs', [StockOutController::class, 'store'])->name('stock-outs.store');

    Route::get('/sisa-display', [SisaDisplayController::class, 'index'])->name('sisa-display.index');
    Route::post('/sisa-display', [SisaDisplayController::class, 'store'])->name('sisa-display.store');

    // Reports
    Route::get('/reports/stock-history', [StockHistoryController::class, 'index'])->name('reports.stock-history');
    Route::get('/reports/daily-stock', [DailyStockController::class, 'index'])->name('reports.daily-stock');
    Route::get('/reports/stock-movement', [StockMovementController::class, 'index'])->name('reports.stock-movement');

    // Administration (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
