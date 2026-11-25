<?php

use Illuminate\Support\Facades\Route;
use PrismCms\Http\Controllers\PageController;

Route::middleware('web')->group(function (): void {
    Route::get('/', PageController::class)->name('page.home');
    Route::fallback(PageController::class)->name('page.show');
});
