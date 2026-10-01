<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionService;
use ChassesAuTresor\Core\Points\PointsService;
use ChassesAuTresor\Core\Points\PurchasePointsService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Create the service responsible for points operations. */
function cat_get_points_service(): PointsService {
    global $wpdb;

    return CoreServiceFactory::points($wpdb);
}

/** Create the service responsible for purchased point packs. */
function cat_get_purchase_points_service(): PurchasePointsService {
    global $wpdb;

    return CoreServiceFactory::purchasePoints($wpdb);
}

/** Create the service responsible for point conversion requests. */
function cat_get_conversion_service(): ConversionService {
    global $wpdb;

    return CoreServiceFactory::conversion($wpdb);
}

if (!function_exists('get_user_points')) {
    /** Return the current or requested user's point balance. */
    function get_user_points($user_id = null): int {
        $userId = (int) ($user_id ?: get_current_user_id());

        return $userId > 0 ? cat_get_points_service()->getBalance($userId) : 0;
    }
}

if (!function_exists('attribuer_points_apres_achat')) {
    function attribuer_points_apres_achat($orderId): void {
        $order = wc_get_order($orderId);
        cat_get_purchase_points_service()->awardOrder($order);
    }
}

if (!function_exists('utilisateur_a_assez_de_points')) {
    function utilisateur_a_assez_de_points(int $userId, int $amount): bool {
        return cat_get_points_service()->hasEnough($userId, $amount);
    }
}

if (!function_exists('deduire_points_utilisateur')) {
    function deduire_points_utilisateur(
        int $userId,
        int $amount,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): void {
        cat_get_points_service()->deduct($userId, $amount, $reason, $originType, $originId);
    }
}

if (!function_exists('ajouter_points_utilisateur')) {
    function ajouter_points_utilisateur(
        int $userId,
        int $amount,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): void {
        cat_get_points_service()->add($userId, $amount, $reason, $originType, $originId);
    }
}
