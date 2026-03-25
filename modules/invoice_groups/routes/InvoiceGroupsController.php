<?php

use Illuminate\Support\Facades\Route;
use Modules\InvoiceGroups\Controllers\InvoiceGroupsController;

// Route registrations for invoice_groups::InvoiceGroupsController.

Route::match(['GET'], 'invoice_groups/index', [InvoiceGroupsController::class, 'index'])->name('invoice_groups.index');
Route::match(['GET'], 'invoice_groups/form', [InvoiceGroupsController::class, 'form'])->name('invoice_groups.form');
Route::match(['GET'], 'invoice_groups/form/{id}', [InvoiceGroupsController::class, 'form'])->name('invoice_groups.form.id');
Route::match(['GET'], 'invoice_groups/delete/{id}', [InvoiceGroupsController::class, 'delete'])->name('invoice_groups.delete.id');
