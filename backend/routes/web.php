<?php

use App\Http\Controllers\BaseUnitController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\InventoryLotController;
use App\Http\Controllers\OpeningInventoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\SalePresentationController;
use App\Http\Controllers\SalePriceController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('categories', CategoryController::class)->except(['show', 'destroy']);
    Route::resource('brands', BrandController::class)->except(['show', 'destroy']);
    Route::resource('base-units', BaseUnitController::class)->except(['show', 'destroy']);
    Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'show', 'store']);
    Route::get('purchases/detailed/create', [PurchaseController::class, 'createDetailed'])->name('purchases.detailed.create');
    Route::post('purchases/detailed', [PurchaseController::class, 'storeDetailed'])->name('purchases.detailed.store');
    Route::get('purchases/{purchase}/detailed/edit', [PurchaseController::class, 'editDetailed'])->name('purchases.detailed.edit');
    Route::patch('purchases/{purchase}/detailed', [PurchaseController::class, 'updateDetailed'])->name('purchases.detailed.update');
    Route::post('purchases/{purchase}/void', [PurchaseController::class, 'void'])->name('purchases.void');
    Route::resource('opening-inventory', OpeningInventoryController::class)->only(['index', 'create', 'store']);
    Route::resource('inventory-lots', InventoryLotController::class)->only(['index']);
    Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'show', 'store']);
    Route::get('sales/search/presentations', [SaleController::class, 'search'])->name('sales.search');
    Route::post('sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');
    Route::resource('receivables', ReceivableController::class)->only(['index', 'show']);
    Route::post('receivables/{receivable}/payments', [ReceivableController::class, 'storePayment'])->name('receivables.payments.store');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('reports/export/sales-summary-csv', [ReportController::class, 'exportSalesSummaryCsv'])->name('reports.export.sales-summary-csv');
    Route::get('reports/export/sales-lines-csv', [ReportController::class, 'exportSalesLinesCsv'])->name('reports.export.sales-lines-csv');
    Route::get('reports/export/purchases-summary-csv', [ReportController::class, 'exportPurchasesSummaryCsv'])->name('reports.export.purchases-summary-csv');
    Route::get('reports/export/purchases-lines-csv', [ReportController::class, 'exportPurchasesLinesCsv'])->name('reports.export.purchases-lines-csv');
    Route::get('reports/export/receivables-csv', [ReportController::class, 'exportReceivablesCsv'])->name('reports.export.receivables-csv');
    Route::get('reports/export/receivable-payments-csv', [ReportController::class, 'exportReceivablePaymentsCsv'])->name('reports.export.receivable-payments-csv');
    Route::get('reports/export/lots-csv', [ReportController::class, 'exportLotsCsv'])->name('reports.export.lots-csv');
    Route::get('reports/export/lot-movements-csv', [ReportController::class, 'exportLotMovementsCsv'])->name('reports.export.lot-movements-csv');
    Route::get('cash', [CashSessionController::class, 'index'])->name('cash.index');
    Route::get('cash/open', [CashSessionController::class, 'create'])->name('cash.create');
    Route::post('cash', [CashSessionController::class, 'store'])->name('cash.store');
    Route::post('cash/{cash}/movements', [CashSessionController::class, 'storeMovement'])->name('cash.movements.store');
    Route::get('cash/{cash}/close', [CashSessionController::class, 'closeForm'])->name('cash.close-form');
    Route::post('cash/{cash}/close', [CashSessionController::class, 'close'])->name('cash.close');

    Route::resource('products.variants', ProductVariantController::class)->except(['show', 'destroy']);
    Route::resource('products.variants.presentations', SalePresentationController::class)->except(['show', 'destroy']);
    Route::resource('products.variants.presentations.prices', SalePriceController::class)->only(['index', 'create', 'store']);
});

require __DIR__.'/auth.php';
