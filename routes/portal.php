<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:customer'])->prefix('portal')->name('portal.')->group(function () {
    Route::view('/', 'portal.placeholder')->name('home');
});
