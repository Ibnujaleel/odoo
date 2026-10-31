<?php

use App\Http\Controllers\QuotationPdfController;
use App\Livewire\Portal\Account;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\Invoices;
use App\Livewire\Portal\Quotations;
use App\Livewire\Portal\QuotationShow;
use App\Livewire\Portal\Subscriptions;
use Illuminate\Support\Facades\Route;

// Quotation PDF route: authenticated staff and authorized owning customers
Route::middleware(['auth'])->get('/portal/quotations/{subscription}/pdf', [QuotationPdfController::class, 'show'])->name('portal.quotations.pdf');

Route::middleware(['auth', 'role:customer'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/quotations', Quotations::class)->name('quotations');
    Route::get('/quotations/{subscription}', QuotationShow::class)->name('quotations.show');
    Route::get('/subscriptions', Subscriptions::class)->name('subscriptions');
    Route::get('/invoices', Invoices::class)->name('invoices');
    Route::get('/account', Account::class)->name('account');
});
