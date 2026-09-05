<?php

declare(strict_types=1);

/**
 * Configuration CRMino. Les durées de rétention du §72 sont CONTRACTUELLES et
 * relèvent de la déclaration CNDP : elles vivent ici, jamais codées en dur dans
 * une requête. Les corriger ne demande aucun déploiement.
 */
return [
    'retention' => [
        // Le journal d'audit porte une valeur contractuelle (§46, §72).
        'audit_annees' => (int) env('CRMINO_RETENTION_AUDIT_ANNEES', 5),
        // Les notifications lues sont opérationnelles : elles s'évaporent vite.
        'notifications_jours' => (int) env('CRMINO_RETENTION_NOTIFS_JOURS', 90),
    ],

    // Suppression par lots : une suppression unique escaladerait les verrous et
    // ferait gonfler le journal des transactions.
    'purge_taille_lot' => (int) env('CRMINO_PURGE_LOT', 5000),
];
