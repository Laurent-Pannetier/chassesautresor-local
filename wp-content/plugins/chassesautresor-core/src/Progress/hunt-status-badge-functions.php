<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatusBadgeService;

if (!function_exists('chasse_preparer_badge_statut')) {
    function chasse_preparer_badge_statut(string $status, ?string $validation): array
    {
        return (new HuntStatusBadgeService())->build($status, $validation);
    }
}
