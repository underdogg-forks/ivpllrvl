<?php

use Illuminate\Support\Facades\Route;
use Modules\Layout\Controllers\LayoutController;

// Auto-generated routes for layout::LayoutController actions.

Route::match(['GET'], 'layout/layout/buffer', [LayoutController::class, 'buffer'])->name('layout.layout.buffer');
Route::match(['GET'], 'layout/layout/load_view', [LayoutController::class, 'load_view'])->name('layout.layout.load_view');
Route::match(['GET'], 'layout/layout/render', [LayoutController::class, 'render'])->name('layout.layout.render');
Route::match(['GET'], 'layout/layout/set', [LayoutController::class, 'set'])->name('layout.layout.set');
