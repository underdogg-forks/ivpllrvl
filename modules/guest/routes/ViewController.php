<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\ViewController;

// Auto-generated routes for guest::ViewController actions.

Route::match(['GET'], 'guest/view/approve_quote', [ViewController::class, 'approve_quote'])->name('guest.view.approve_quote');
Route::match(['GET'], 'guest/view/generate_invoice_pdf', [ViewController::class, 'generate_invoice_pdf'])->name('guest.view.generate_invoice_pdf');
Route::match(['GET'], 'guest/view/generate_quote_pdf', [ViewController::class, 'generate_quote_pdf'])->name('guest.view.generate_quote_pdf');
Route::match(['GET'], 'guest/view/generate_sumex_pdf', [ViewController::class, 'generate_sumex_pdf'])->name('guest.view.generate_sumex_pdf');
Route::match(['GET'], 'guest/view/invoice', [ViewController::class, 'invoice'])->name('guest.view.invoice');
Route::match(['GET'], 'guest/view/quote', [ViewController::class, 'quote'])->name('guest.view.quote');
Route::match(['GET'], 'guest/view/reject_quote', [ViewController::class, 'reject_quote'])->name('guest.view.reject_quote');
