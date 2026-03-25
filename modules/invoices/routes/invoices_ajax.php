<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\InvoicesAjaxController;

// Auto-generated routes for invoices::InvoicesAjaxController actions.

Route::post( 'invoices/invoicesajax/change_client', [InvoicesAjaxController::class, 'change_client'])->name('invoices.invoicesajax.change_client');
Route::post( 'invoices/invoicesajax/change_user', [InvoicesAjaxController::class, 'change_user'])->name('invoices.invoicesajax.change_user');
Route::post( 'invoices/invoicesajax/copy_invoice', [InvoicesAjaxController::class, 'copy_invoice'])->name('invoices.invoicesajax.copy_invoice');
Route::post( 'invoices/invoicesajax/create', [InvoicesAjaxController::class, 'create'])->name('invoices.invoicesajax.create');
Route::post( 'invoices/invoicesajax/create_credit', [InvoicesAjaxController::class, 'create_credit'])->name('invoices.invoicesajax.create_credit');
Route::post( 'invoices/invoicesajax/create_recurring', [InvoicesAjaxController::class, 'create_recurring'])->name('invoices.invoicesajax.create_recurring');
Route::post( 'invoices/invoicesajax/delete_item', [InvoicesAjaxController::class, 'delete_item'])->name('invoices.invoicesajax.delete_item');
Route::post( 'invoices/invoicesajax/get_item', [InvoicesAjaxController::class, 'get_item'])->name('invoices.invoicesajax.get_item');
Route::post( 'invoices/invoicesajax/get_recur_start_date', [InvoicesAjaxController::class, 'get_recur_start_date'])->name('invoices.invoicesajax.get_recur_start_date');
Route::post( 'invoices/invoicesajax/modal_change_client', [InvoicesAjaxController::class, 'modal_change_client'])->name('invoices.invoicesajax.modal_change_client');
Route::post( 'invoices/invoicesajax/modal_change_user', [InvoicesAjaxController::class, 'modal_change_user'])->name('invoices.invoicesajax.modal_change_user');
Route::post( 'invoices/invoicesajax/modal_copy_invoice', [InvoicesAjaxController::class, 'modal_copy_invoice'])->name('invoices.invoicesajax.modal_copy_invoice');
Route::post( 'invoices/invoicesajax/modal_create_credit', [InvoicesAjaxController::class, 'modal_create_credit'])->name('invoices.invoicesajax.modal_create_credit');
Route::post( 'invoices/invoicesajax/modal_create_invoice', [InvoicesAjaxController::class, 'modal_create_invoice'])->name('invoices.invoicesajax.modal_create_invoice');
Route::post( 'invoices/invoicesajax/modal_create_recurring', [InvoicesAjaxController::class, 'modal_create_recurring'])->name('invoices.invoicesajax.modal_create_recurring');
Route::post( 'invoices/invoicesajax/save', [InvoicesAjaxController::class, 'save'])->name('invoices.invoicesajax.save');
Route::post( 'invoices/invoicesajax/save_invoice_tax_rate', [InvoicesAjaxController::class, 'save_invoice_tax_rate'])->name('invoices.invoicesajax.save_invoice_tax_rate');
