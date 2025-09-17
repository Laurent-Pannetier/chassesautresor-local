<?php
/**
 * Points section for "Mon Compte".
 *
 * Points cards and history have been removed from this area.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (current_user_can('administrator')) {
    return;
}

$current_user = wp_get_current_user();
$roles        = (array) $current_user->roles;

if (in_array(ROLE_ORGANISATEUR, $roles, true) || in_array(ROLE_ORGANISATEUR_CREATION, $roles, true)) {
    return;
}
