<?php

declare(strict_types=1);

use AhmedAliraqi\UiManager\Http\Controllers\Api\MediaController;
use AhmedAliraqi\UiManager\Http\Controllers\Api\PageController;
use AhmedAliraqi\UiManager\Http\Controllers\Api\SectionController;
use AhmedAliraqi\UiManager\Http\Controllers\Api\VariableController;
use Illuminate\Support\Facades\Route;

// Pages
Route::get('pages', [PageController::class, 'index'])->name('ui-manager.api.pages.index');
Route::get('pages/{page}', [PageController::class, 'show'])->name('ui-manager.api.pages.show');

// Sections (non-repeatable)
Route::get('pages/{page}/sections/{section}', [SectionController::class, 'show'])->name('ui-manager.api.sections.show');
Route::put('pages/{page}/sections/{section}', [SectionController::class, 'update'])->name('ui-manager.api.sections.update');

// Repeatable section items
Route::post('pages/{page}/sections/{section}/items', [SectionController::class, 'storeItem'])->name('ui-manager.api.sections.items.store');
Route::put('pages/{page}/sections/{section}/items/{item}', [SectionController::class, 'updateItem'])->name('ui-manager.api.sections.items.update');
Route::delete('pages/{page}/sections/{section}/items/{item}', [SectionController::class, 'destroyItem'])->name('ui-manager.api.sections.items.destroy');
Route::post('pages/{page}/sections/{section}/reorder', [SectionController::class, 'reorder'])->name('ui-manager.api.sections.reorder');

// Media
Route::post('media', [MediaController::class, 'store'])->name('ui-manager.api.media.store');
Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('ui-manager.api.media.destroy');

// Variables
Route::get('variables', [VariableController::class, 'index'])->name('ui-manager.api.variables.index');
