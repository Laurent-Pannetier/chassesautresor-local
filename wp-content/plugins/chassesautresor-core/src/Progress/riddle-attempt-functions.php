<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Create the service responsible for riddle attempts. */
function cat_get_riddle_attempt_service(): RiddleAttemptService {
    global $wpdb;

    return CoreServiceFactory::riddleAttempts($wpdb);
}

/** Count attempts awaiting a manual decision for a riddle. */
function compter_tentatives_en_attente(int $enigme_id): int {
    return cat_get_riddle_attempt_service()->countPendingForRiddle($enigme_id);
}

/** Normalize the historical ACF representation of a riddle validation mode. */
function enigme_normaliser_mode_validation($mode): string {
    if (is_array($mode)) {
        $mode = $mode['value'] ?? '';
    }

    $mode = strtolower(trim((string) $mode));
    if ($mode === '' || strpos($mode, 'aucune') === 0 || $mode === 'none') {
        return 'aucune';
    }

    return $mode;
}
