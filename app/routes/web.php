<?php

use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::get('favicon-proxy', \App\Http\Controllers\FaviconProxyController::class)->name('favicon.proxy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('dashboard/refresh', function () {
        Artisan::call('services:check');
        return redirect()->route('dashboard');
    })->name('dashboard.refresh');

    Route::resource('services', ServiceController::class)->except(['create', 'edit', 'index', 'show']);
    Route::get('services/{service}/checks', [ServiceController::class, 'checks'])->name('services.checks');

    Route::resource('admin-portals', AdminPortalController::class)->except(['create', 'edit', 'index', 'show']);
    Route::post('admin-portals/{adminPortal}/refresh-screenshot', [AdminPortalController::class, 'refreshScreenshot'])->name('admin-portals.refresh-screenshot');
});

require __DIR__.'/settings.php';
