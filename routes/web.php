<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// Public marketing site
Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/about', [PublicController::class, 'about'])->name('public.about');
Route::get('/features', [PublicController::class, 'features'])->name('public.features');
Route::get('/services', [PublicController::class, 'services'])->name('public.services');
Route::get('/faq', [PublicController::class, 'faq'])->name('public.faq');
Route::get('/contact', [PublicController::class, 'contact'])->name('public.contact');
Route::post('/contact', [PublicController::class, 'submitContact'])
    ->middleware('throttle:contact-form')
    ->name('public.contact.submit');

Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::post('organization', [\App\Http\Controllers\Admin\OrganizationController::class, 'update'])
        ->name('organization.update');

    Route::resource('companies', \App\Http\Controllers\Admin\CompanyController::class);

    Route::resource('orders', \App\Http\Controllers\Admin\SalesOrderController::class)->only(['index', 'show', 'edit', 'update']);
    Route::get('orders/{order}/generate-invoice', [\App\Http\Controllers\Admin\InvoiceController::class, 'createNext'])
        ->name('orders.generate-invoice.create');
    Route::post('orders/{order}/generate-invoice', [\App\Http\Controllers\Admin\InvoiceController::class, 'storeNext'])
        ->name('orders.generate-invoice.store');
    Route::get('invoices/create', [\App\Http\Controllers\Admin\InvoiceController::class, 'create'])
        ->name('invoices.create');
    Route::get('invoices/random/create', [\App\Http\Controllers\Admin\InvoiceController::class, 'createRandom'])
        ->name('invoices.random.create');
    Route::post('invoices/random', [\App\Http\Controllers\Admin\InvoiceController::class, 'storeRandom'])
        ->name('invoices.random.store');
    Route::post('invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'store'])
        ->name('invoices.store');
    Route::get('invoices/{invoice}/edit', [\App\Http\Controllers\Admin\InvoiceController::class, 'edit'])
        ->name('invoices.edit');
    Route::put('invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'update'])
        ->name('invoices.update');
    Route::post('invoices/{invoice}/finalize', [\App\Http\Controllers\Admin\InvoiceController::class, 'finalize'])
        ->name('invoices.finalize');
    Route::post('invoices/{invoice}/send-email', [\App\Http\Controllers\Admin\InvoiceController::class, 'sendEmail'])
        ->middleware('throttle:invoice-email')
        ->name('invoices.send-email');
    Route::get('invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])
        ->name('invoices.history');
    Route::get('invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'show'])
        ->name('invoices.show');
    Route::delete('invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'destroy'])
        ->name('invoices.destroy');
    Route::get('/invoices/{invoice}/pdf', [\App\Http\Controllers\Admin\InvoiceController::class, 'downloadPdf'])
        ->middleware('throttle:invoice-pdf')
        ->name('invoices.pdf');
    Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])
        ->name('reports.index');
    Route::get('reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])
        ->name('reports.export');
    Route::post('invoices/{invoice}/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'store'])
        ->name('payments.store');
        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'edit'])
        ->name('settings.edit');
    Route::post('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])
        ->name('settings.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';