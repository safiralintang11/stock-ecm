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
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::middleware('admin')->group(function () {
        Route::get('/products/template', [ProductController::class, 'template'])->name('products.template');
        Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Stock
    Route::get('/main-stocks', [MainStockController::class, 'index'])->name('main-stocks.index');
    Route::get('/main-stocks/export', [MainStockController::class, 'export'])->name('main-stocks.export');
    Route::get('/main-stocks/template', [MainStockController::class, 'template'])->name('main-stocks.template');
    Route::post('/main-stocks', [MainStockController::class, 'store'])->name('main-stocks.store');
    Route::post('/main-stocks/import', [MainStockController::class, 'import'])->name('main-stocks.import');

    Route::get('/display-stocks', [DisplayStockController::class, 'index'])->name('display-stocks.index');
    Route::get('/display-stocks/export', [DisplayStockController::class, 'export'])->name('display-stocks.export');
    Route::get('/display-stocks/template', [DisplayStockController::class, 'template'])->name('display-stocks.template');
    Route::post('/display-stocks', [DisplayStockController::class, 'store'])->name('display-stocks.store');
    Route::post('/display-stocks/import', [DisplayStockController::class, 'import'])->name('display-stocks.import');

    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::get('/stock-transfers/export', [StockTransferController::class, 'export'])->name('stock-transfers.export');
    Route::get('/stock-transfers/template', [StockTransferController::class, 'template'])->name('stock-transfers.template');
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::post('/stock-transfers/import', [StockTransferController::class, 'import'])->name('stock-transfers.import');

    Route::get('/stock-outs', [StockOutController::class, 'index'])->name('stock-outs.index');
    Route::get('/stock-outs/export', [StockOutController::class, 'export'])->name('stock-outs.export');
    Route::get('/stock-outs/template', [StockOutController::class, 'template'])->name('stock-outs.template');
    Route::post('/stock-outs', [StockOutController::class, 'store'])->name('stock-outs.store');
    Route::post('/stock-outs/import', [StockOutController::class, 'import'])->name('stock-outs.import');

    Route::get('/sisa-display', [SisaDisplayController::class, 'index'])->name('sisa-display.index');
    Route::get('/sisa-display/export', [SisaDisplayController::class, 'export'])->name('sisa-display.export');
    Route::get('/sisa-display/template', [SisaDisplayController::class, 'template'])->name('sisa-display.template');
    Route::post('/sisa-display', [SisaDisplayController::class, 'store'])->name('sisa-display.store');
    Route::post('/sisa-display/import', [SisaDisplayController::class, 'import'])->name('sisa-display.import');

    // Reports
    Route::get('/reports/stock-history', [StockHistoryController::class, 'index'])->name('reports.stock-history');
    Route::get('/reports/stock-history/export', [StockHistoryController::class, 'export'])->name('reports.stock-history.export');
    Route::get('/reports/daily-stock', [DailyStockController::class, 'index'])->name('reports.daily-stock');
    Route::get('/reports/daily-stock/export', [DailyStockController::class, 'export'])->name('reports.daily-stock.export');
    Route::get('/reports/stock-movement', [StockMovementController::class, 'index'])->name('reports.stock-movement');
    Route::get('/reports/stock-movement/export', [StockMovementController::class, 'export'])->name('reports.stock-movement.export');

    // Administration (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::get('/stock-adjustments/export', [StockAdjustmentController::class, 'export'])->name('stock-adjustments.export');
        Route::get('/stock-adjustments/template', [StockAdjustmentController::class, 'template'])->name('stock-adjustments.template');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
        Route::post('/stock-adjustments/import', [StockAdjustmentController::class, 'import'])->name('stock-adjustments.import');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('/users/template', [UserController::class, 'template'])->name('users.template');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
