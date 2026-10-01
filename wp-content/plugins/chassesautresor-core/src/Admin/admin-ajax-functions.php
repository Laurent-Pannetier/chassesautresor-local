<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Admin\AdminAjaxHandler;

if (!function_exists('rechercher_utilisateur_ajax')) {
    function rechercher_utilisateur_ajax(): void {
        AdminAjaxHandler::searchUsers();
    }
}
if (!function_exists('ajax_lister_historique_paiements_admin')) {
    function ajax_lister_historique_paiements_admin(): void {
        AdminAjaxHandler::listPayments();
    }
}
if (!function_exists('ajax_update_request_status')) {
    function ajax_update_request_status(): void {
        AdminAjaxHandler::updateConversionStatus();
    }
}
if (!function_exists('recuperer_details_acf')) {
    function recuperer_details_acf(): void {
        AdminAjaxHandler::inspectAcf();
    }
}
if (!function_exists('cta_reset_stats')) {
    function cta_reset_stats(): void {
        AdminAjaxHandler::resetStatistics();
    }
}
if (!function_exists('cta_toggle_site_protection')) {
    function cta_toggle_site_protection(): void {
        AdminAjaxHandler::toggleSiteProtection();
    }
}
