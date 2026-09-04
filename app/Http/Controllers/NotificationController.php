<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function lu(Request $request, Notification $notification): RedirectResponse
    {
        // Gardée par l'appartenance, pas par une permission (§36). 404 pour ne
        // pas révéler l'existence d'une notification d'autrui.
        abort_unless($notification->utilisateur_id === $request->user()->id, 404);

        // On marque lu AVANT de naviguer : la navigation démonte la page, un
        // appel lancé après ne se résoudrait jamais.
        if ($notification->lue_le === null) {
            $notification->update(['lue_le' => now()]);
        }

        $chemin = $this->chemin($notification);

        return $chemin !== null ? redirect($chemin) : back();
    }

    public function toutLu(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('utilisateur_id', $request->user()->id)
            ->whereNull('lue_le')
            ->update(['lue_le' => now()]);

        return back();
    }

    /** Le chemin de la cible, ou null (transfert de portefeuille : aucun lien). */
    private function chemin(Notification $n): ?string
    {
        return match ($n->cible_type) {
            'Lead' => "/leads/{$n->cible_id}",
            'Societe' => "/societes/{$n->cible_id}",
            'Opportunite' => "/opportunites/{$n->cible_id}",
            'Tache' => '/taches',
            default => null,
        };
    }
}
