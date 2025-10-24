<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoices/{invoice}/pdf', function (App\Models\Invoice $invoice) {
    $invoice->load(['customer', 'items', 'company']);
    return app(App\Services\InvoicePdfService::class)->download($invoice);
})->name('invoices.pdf');
