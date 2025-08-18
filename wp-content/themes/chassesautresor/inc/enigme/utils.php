<?php
defined('ABSPATH') || exit;

/**
 * Determine whether an enigma requires payment to attempt.
 *
 * An enigma is considered paid if it has a positive attempt cost and
 * a validation mode different from "aucune".
 *
 * @param int $enigme_id Enigma post ID.
 * @return bool True when the enigma is paid.
 */
function enigme_is_paid(int $enigme_id): bool
{
    if (!$enigme_id || get_post_type($enigme_id) !== 'enigme') {
        return false;
    }

    $cost = (int) get_field('enigme_tentative_cout_points', $enigme_id);
    $mode = strtolower((string) get_field('enigme_mode_validation', $enigme_id));

    return $cost > 0 && $mode !== 'aucune';
}
