<?php
defined('ABSPATH') || exit();

/**
 * Vérifie si une chasse est marquée comme démo.
 *
 * @param int $chasse_id Identifiant de la chasse.
 *
 * @return bool
 */
function ca_demo_is_demo_hunt(int $chasse_id): bool
{
    static $cache = [];

    if ($chasse_id <= 0) {
        return false;
    }

    if (isset($cache[$chasse_id])) {
        return $cache[$chasse_id];
    }

    if (!function_exists('get_organisateur_from_chasse')) {
        $cache[$chasse_id] = false;

        return $cache[$chasse_id];
    }

    $organisateur_id = get_organisateur_from_chasse($chasse_id);
    if (!$organisateur_id) {
        $cache[$chasse_id] = false;

        return $cache[$chasse_id];
    }

    $configured_logins = CA_DEMO_ORGANISATEUR_LOGINS;
    if (!is_array($configured_logins)) {
        $configured_logins = [];
    }

    $configured_logins = array_filter(array_map(
        static function ($login) {
            $login = is_string($login) ? trim($login) : '';

            return $login !== '' ? strtolower($login) : null;
        },
        $configured_logins
    ));

    /** @var string[] $allowed_logins */
    $allowed_logins = apply_filters('ca_demo_organisateur_logins', array_values($configured_logins));
    $allowed_logins = array_filter(array_map(
        static function ($login) {
            return is_string($login) ? strtolower(trim($login)) : null;
        },
        $allowed_logins
    ));

    if (empty($allowed_logins)) {
        $cache[$chasse_id] = false;

        return $cache[$chasse_id];
    }

    $associated_users = function_exists('get_field')
        ? get_field('utilisateurs_associes', $organisateur_id)
        : [];
    if (!is_array($associated_users) || empty($associated_users)) {
        $cache[$chasse_id] = false;

        return $cache[$chasse_id];
    }

    $is_demo = false;

    foreach ($associated_users as $user_entry) {
        if ($user_entry instanceof WP_User) {
            $user = $user_entry;
        } elseif (is_array($user_entry) && isset($user_entry['ID'])) {
            $user = get_user_by('id', (int) $user_entry['ID']);
        } elseif (is_numeric($user_entry)) {
            $user = get_user_by('id', (int) $user_entry);
        } else {
            $user = null;
        }

        if (!$user instanceof WP_User) {
            continue;
        }

        $login = strtolower($user->user_login);
        if (in_array($login, $allowed_logins, true)) {
            $is_demo = true;
            break;
        }
    }

    $filtered = (bool) apply_filters(
        'ca_demo_is_demo_hunt',
        $is_demo,
        $chasse_id,
        [
            'organisateur_id' => $organisateur_id,
            'allowed_logins'  => $allowed_logins,
        ]
    );

    $cache[$chasse_id] = $filtered;

    return $cache[$chasse_id];
}
