<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStatusAjaxHandler;
use ChassesAuTresor\Core\Progress\RiddleSystemStateUpdater;

if (!function_exists('mettre_a_jour_statuts_enigmes_de_la_chasse')) {
    function mettre_a_jour_statuts_enigmes_de_la_chasse(int $huntId, ?string $huntStatus = null): void
    {
        (new RiddleSystemStateUpdater())->refreshHunt($huntId, $huntStatus);
    }
}

if (!function_exists('enigme_mettre_a_jour_etat_systeme')) {
    function enigme_mettre_a_jour_etat_systeme(
        int $riddleId,
        bool $persist = true,
        ?string $huntStatus = null
    ): string {
        return (new RiddleSystemStateUpdater())->refresh($riddleId, $persist, $huntStatus);
    }
}

if (!function_exists('enigme_mettre_a_jour_etat_systeme_automatiquement')) {
    function enigme_mettre_a_jour_etat_systeme_automatiquement($postId): void
    {
        if (is_numeric($postId)) {
            (new RiddleSystemStateUpdater())->refresh((int) $postId);
        }
    }
}

if (!function_exists('forcer_recalcul_statut_enigme')) {
    function forcer_recalcul_statut_enigme(): void
    {
        RiddleStatusAjaxHandler::handle();
    }
}

if (!function_exists('enigme_get_etat_systeme')) {
    function enigme_get_etat_systeme(int $riddleId): string
    {
        return get_field('enigme_cache_etat_systeme', $riddleId) ?: 'invalide';
    }
}
