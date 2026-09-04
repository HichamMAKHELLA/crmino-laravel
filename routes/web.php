<?php

use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TacheController;
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
    Route::get('opportunites/{opportunite}', [OpportuniteController::class, 'show'])->name('opportunites.show');
    Route::post('opportunites/{opportunite}/gagner', [OpportuniteController::class, 'gagner'])->name('opportunites.gagner');
    Route::post('opportunites/{opportunite}/perdre', [OpportuniteController::class, 'perdre'])->name('opportunites.perdre');

    Route::post('activites', [ActiviteController::class, 'store'])->name('activites.store');

    Route::get('taches', [TacheController::class, 'index'])->name('taches.index');
    Route::post('taches', [TacheController::class, 'store'])->name('taches.store');
    Route::post('taches/{tache}/terminer', [TacheController::class, 'terminer'])->name('taches.terminer');

    Route::post('notifications/{notification}/lu', [NotificationController::class, 'lu'])->name('notifications.lu');
    Route::post('notifications/tout-lu', [NotificationController::class, 'toutLu'])->name('notifications.tout-lu');

    Route::get('campagnes', [CampagneController::class, 'index'])->name('campagnes.index');
    Route::get('campagnes/create', [CampagneController::class, 'create'])->name('campagnes.create');
    Route::post('campagnes', [CampagneController::class, 'store'])->name('campagnes.store');
    Route::get('campagnes/{campagne}', [CampagneController::class, 'show'])->name('campagnes.show');

    Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
});

require __DIR__.'/settings.php';
