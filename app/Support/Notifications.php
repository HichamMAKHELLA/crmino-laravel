<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Notification;

/**
 * Production des notifications (§36). CRMino SIGNALE, il n'agit pas : une
 * notification informe, elle ne crée jamais de tâche d'office.
 */
final class Notifications
{
    public static function notifier(
        int $destinataireId,
        string $titre,
        ?string $texte = null,
        ?string $cibleType = null,
        ?int $cibleId = null,
    ): Notification {
        return Notification::create([
            'utilisateur_id' => $destinataireId,
            'titre' => $titre,
            'texte' => $texte,
            'cible_type' => $cibleType,
            'cible_id' => $cibleId,
            'cree_le' => now(),
        ]);
    }
}
