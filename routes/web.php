<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportuniteController;
use App\Http\Controllers\SocieteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::post('leads/{lead}/convertir', [LeadController::class, 'convertir'])->name('leads.convertir');

    Route::get('societes', [SocieteController::class, 'index'])->name('societes.index');
    Route::get('societes/{societe}', [SocieteController::class, 'show'])->name('societes.show');

    Route::get('opportunites', [OpportuniteController::class, 'index'])->name('opportunites.index');
    Route::get('opportunites/create', [OpportuniteController::class, 'create'])->name('opportunites.create');
    Route::post('opportunites', [OpportuniteController::class, 'store'])->name('opportunites.store');
    Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
});

require __DIR__.'/settings.php';
