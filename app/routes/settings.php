<?php

use App\Http\Controllers\Settings\NotificationEmailController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SmtpSettingController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/smtp', [SmtpSettingController::class, 'edit'])->name('smtp.edit');
    Route::put('settings/smtp', [SmtpSettingController::class, 'update'])->name('smtp.update');
    Route::post('settings/smtp/test', [SmtpSettingController::class, 'test'])->name('smtp.test');

    Route::get('settings/users', [UserController::class, 'index'])->name('users.index');
    Route::post('settings/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('settings/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('settings/users/{user}/toggle-edit', [UserController::class, 'toggleEdit'])->name('users.toggle-edit');
    Route::put('settings/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.update-password');

    Route::get('settings/notifications', [NotificationEmailController::class, 'index'])->name('notifications.index');
    Route::post('settings/notifications', [NotificationEmailController::class, 'store'])->name('notifications.store');
    Route::patch('settings/notifications/interval', [NotificationEmailController::class, 'updateInterval'])->name('notifications.interval');
    Route::delete('settings/notifications/{notificationEmail}', [NotificationEmailController::class, 'destroy'])->name('notifications.destroy');
    Route::patch('settings/notifications/{notificationEmail}/toggle', [NotificationEmailController::class, 'toggle'])->name('notifications.toggle');
});
