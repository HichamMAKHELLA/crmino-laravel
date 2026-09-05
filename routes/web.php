<?php

use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\DonneesPersonnellesController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportuniteController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\SocieteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::post('leads/{lead}/convertir', [LeadController::class, 'convertir'])->name('leads.convertir');

    Route::get('societes', [SocieteController::class, 'index'])->name('societes.index');
    Route::get('societes/{societe}', [SocieteController::class, 'show'])->name('societes.show');
    Route::put('societes/{societe}', [SocieteController::class, 'update'])->name('societes.update');
    Route::post('societes/{societe}/etat', [SocieteController::class, 'etat'])->name('societes.etat');

    Route::get('opportunites', [OpportuniteController::class, 'index'])->name('opportunites.index');
    Route::get('opportunites/create', [OpportuniteController::class, 'create'])->name('opportunites.create');
    Route::post('opportunites', [OpportuniteController::class, 'store'])->name('opportunites.store');
    Route::get('opportunites/{opportunite}', [OpportuniteController::class, 'show'])->name('opportunites.show');
    Route::put('opportunites/{opportunite}', [OpportuniteController::class, 'update'])->name('opportunites.update');
    Route::post('opportunites/{opportunite}/etape', [OpportuniteController::class, 'deplacer'])->name('opportunites.deplacer');
    Route::post('opportunites/{opportunite}/gagner', [OpportuniteController::class, 'gagner'])->name('opportunites.gagner');
    Route::post('opportunites/{opportunite}/perdre', [OpportuniteController::class, 'perdre'])->name('opportunites.perdre');
    Route::post('opportunites/{opportunite}/lignes', [OpportuniteController::class, 'lignes'])->name('opportunites.lignes');

    Route::post('activites', [ActiviteController::class, 'store'])->name('activites.store');

    // Documents §44
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/telecharger', [DocumentController::class, 'download'])->name('documents.download');
    Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Notes internes §45 — pas de permission dédiée, gardées par la fiche parente.
    Route::post('commentaires', [CommentaireController::class, 'store'])->name('commentaires.store');
    Route::put('commentaires/{commentaire}', [CommentaireController::class, 'update'])->name('commentaires.update');
    Route::delete('commentaires/{commentaire}', [CommentaireController::class, 'destroy'])->name('commentaires.destroy');

    Route::get('taches', [TacheController::class, 'index'])->name('taches.index');
    Route::post('taches', [TacheController::class, 'store'])->name('taches.store');
    Route::post('taches/{tache}/terminer', [TacheController::class, 'terminer'])->name('taches.terminer');

    Route::post('notifications/{notification}/lu', [NotificationController::class, 'lu'])->name('notifications.lu');
    Route::post('notifications/tout-lu', [NotificationController::class, 'toutLu'])->name('notifications.tout-lu');

    Route::get('campagnes', [CampagneController::class, 'index'])->name('campagnes.index');
    Route::get('campagnes/create', [CampagneController::class, 'create'])->name('campagnes.create');
    Route::post('campagnes', [CampagneController::class, 'store'])->name('campagnes.store');
    Route::get('campagnes/{campagne}', [CampagneController::class, 'show'])->name('campagnes.show');

    Route::get('catalogue', [CatalogueController::class, 'index'])->name('catalogue.index');
    Route::get('catalogue/create', [CatalogueController::class, 'create'])->name('catalogue.create');
    Route::post('catalogue', [CatalogueController::class, 'store'])->name('catalogue.store');
    Route::post('catalogue/{produit}/desactiver', [CatalogueController::class, 'desactiver'])->name('catalogue.desactiver');
    Route::post('catalogue/{produit}/reactiver', [CatalogueController::class, 'reactiver'])->name('catalogue.reactiver');

    Route::get('import', [ImportController::class, 'index'])->name('import.index');
    Route::post('import/analyser', [ImportController::class, 'analyser'])->name('import.analyser');
    Route::post('import/executer', [ImportController::class, 'executer'])->name('import.executer');

    Route::get('rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::get('journal', [JournalController::class, 'index'])->name('journal.index');

    Route::get('donnees-personnelles', [DonneesPersonnellesController::class, 'index'])->name('donnees.index');
    Route::post('donnees-personnelles/contact/{contact}', [DonneesPersonnellesController::class, 'dossier'])->name('donnees.dossier');

    Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
});

require __DIR__.'/settings.php';
