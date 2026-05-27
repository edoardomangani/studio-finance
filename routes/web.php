<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Design system page: showroom dei componenti, accessibile solo in dev.
    // In production la rotta non esiste affatto.
    if (app()->environment('local')) {
        Route::inertia('design-system', 'DesignSystem')->name('design-system');
    }
});

require __DIR__.'/settings.php';
