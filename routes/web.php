<?php

use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
});

require __DIR__.'/settings.php';
