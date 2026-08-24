<?php

use Illuminate\Support\Facades\Route;
use LaravelGuard\Ui\Http\Controllers\DashboardController;

$path = trim((string) config('laravel-guard.ui.path', 'laravel-guard'), '/');
$middleware = array_values(array_filter((array) config('laravel-guard.ui.middleware', ['web', 'auth'])));
$middleware[] = 'laravel-guard.ui.authorize';
$middleware[] = 'throttle:120,1';

Route::prefix($path)->middleware($middleware)->group(function (): void {
    Route::get('/', [DashboardController::class, 'show'])->name('laravel-guard.ui.overview');
    Route::get('/assets/app.css', [DashboardController::class, 'asset'])->name('laravel-guard.ui.asset');
    Route::get('/{section}', [DashboardController::class, 'show'])->name('laravel-guard.ui.section');
    Route::post('/scans', [DashboardController::class, 'scan'])->middleware('throttle:3,1')->name('laravel-guard.ui.scan');
});
