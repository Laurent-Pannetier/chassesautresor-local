<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatusAjaxHandler;
use ChassesAuTresor\Core\Progress\HuntStatusUpdater;

if (!function_exists('verifier_ou_recalculer_statut_chasse')) {
    function verifier_ou_recalculer_statut_chasse(int $huntId): void
    {
        (new HuntStatusUpdater())->refreshIfStale($huntId);
    }
}

if (!function_exists('mettre_a_jour_statuts_chasse')) {
    function mettre_a_jour_statuts_chasse(int $huntId): string
    {
        return (new HuntStatusUpdater())->refresh($huntId);
    }
}

if (!function_exists('forcer_recalcul_statut_chasse')) {
    function forcer_recalcul_statut_chasse(): void
    {
        HuntStatusAjaxHandler::recalculate();
    }
}

if (!function_exists('recuperer_statut_chasse')) {
    function recuperer_statut_chasse(): void
    {
        HuntStatusAjaxHandler::getStatus();
    }
}

if (!function_exists('forcer_statut_apres_acf')) {
    function forcer_statut_apres_acf(int $huntId, ?string $validation = null): void
    {
        (new HuntStatusUpdater())->synchronizePublication($huntId, $validation);
    }
}
