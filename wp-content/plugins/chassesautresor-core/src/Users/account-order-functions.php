<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\AccountOrdersRenderer;

if (!function_exists('afficher_commandes_utilisateur')) {
    function afficher_commandes_utilisateur($userId, $limit = 4): string {
        return (new AccountOrdersRenderer())->render((int) $userId, (int) $limit);
    }
}
