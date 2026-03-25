<?php

use Illuminate\Support\Facades\Route;
use Modules\InvoiceGroups\Controllers\InvoiceGroupsController;

// Auto-generated routes for invoice_groups::InvoiceGroupsController actions.

Route::match(['POST'], 'invoice_groups/invoicegroups/delete', [InvoiceGroupsController::class, 'delete'])->name('invoice_groups.invoicegroups.delete');
Route::match(['GET'], 'invoice_groups/invoicegroups/form', [InvoiceGroupsController::class, 'form'])->name('invoice_groups.invoicegroups.form');
Route::match(['GET'], 'invoice_groups/invoicegroups/index', [InvoiceGroupsController::class, 'index'])->name('invoice_groups.invoicegroups.index');
