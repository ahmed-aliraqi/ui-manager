<?php

declare(strict_types=1);

use AhmedAliraqi\UiManager\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Catch-all for the SPA — must NOT match the api/* prefix segment.
Route::get('/{any?}', DashboardController::class . '@index')
    ->where('any', '^(?!api/).*')
    ->name('ui-manager.dashboard');
