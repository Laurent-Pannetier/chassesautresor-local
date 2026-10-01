<?php

declare(strict_types=1);

/**
 * Determine whether the current user may view statistics for a hunt.
 */
function utilisateur_peut_voir_statistiques_chasse(int $chasse_id): bool
{
    $has_valid_hunt = $chasse_id > 0;
    $is_administrator = $has_valid_hunt && current_user_can('manage_options');
    $service = new ChassesAuTresor\Core\Content\HuntAccessService();

    return $service->canViewStatistics(
        $has_valid_hunt,
        $is_administrator,
        $has_valid_hunt
            && !$is_administrator
            && utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id)
    );
}

/**
 * Vérifie si un utilisateur possède un rôle d'organisateur.
 *
 * L'utilisateur peut être organisateur confirmé ou en cours de création.
 * Si aucun ID n'est fourni, l'utilisateur courant est utilisé.
 *
 * @param int|null $user_id ID de l'utilisateur ou null pour courant.
 * @return bool True si l'utilisateur a un rôle d'organisateur.
 */
function est_organisateur($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    $roles = (array) $user->roles;
    $service = new ChassesAuTresor\Core\Content\OrganizerRoleService();

    return $service->isOrganizer(
        $roles,
        ROLE_ORGANISATEUR,
        ROLE_ORGANISATEUR_CREATION
    );
}

/**
 * Determine if the current user can perform an action on indices for a given object.
 *
 * @param string $action      Action to check: 'create', 'edit', or 'delete'.
 * @param string $object_type Target type: 'chasse' or 'enigme'.
 * @param int    $object_id   ID of the target object.
 *
 * @return bool True if allowed, false otherwise.
 */
function indice_action_autorisee(string $action, string $object_type, int $object_id): bool
{
    $is_authenticated = is_user_logged_in();
    $is_valid_object = $is_authenticated && get_post_type($object_id) === $object_type;
    $hunt_id = 0;
    if ($is_valid_object && $object_type === 'enigme') {
        $hunt_id = (int) recuperer_id_chasse_associee($object_id);
    } elseif ($is_valid_object && $object_type === 'chasse') {
        $hunt_id = $object_id;
    }
    $has_hunt = $hunt_id > 0;
    $needs_hunt_permission = $object_type === 'enigme' && in_array($action, ['create', 'edit'], true);
    $service = new ChassesAuTresor\Core\Content\RelatedContentActionService();

    return $service->canPerform(
        $is_authenticated,
        $action,
        $object_type,
        $is_valid_object,
        $is_authenticated && current_user_can('manage_options'),
        $is_valid_object
            && $has_hunt
            && utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $hunt_id),
        $is_valid_object ? (string) get_post_status($object_id) : '',
        $object_type === 'chasse' && $is_valid_object
            ? (string) get_field('chasse_cache_statut_validation', $object_id)
            : '',
        $object_type === 'enigme' && $has_hunt,
        $needs_hunt_permission && $has_hunt
            ? indice_action_autorisee($action, 'chasse', $hunt_id)
            : false
    );
}

/**
 * Détermine si une action sur une solution est autorisée.
 *
 * Wrapper autour de indice_action_autorisee afin de réutiliser les
 * règles d'accès existantes pour les chasses et les énigmes.
 *
 * @param string $action      Action souhaitée (create, edit, delete).
 * @param string $object_type Type de cible (chasse ou enigme).
 * @param int    $object_id   ID de la cible.
 * @return bool
 */
function solution_action_autorisee(string $action, string $object_type, int $object_id): bool
{
    return indice_action_autorisee($action, $object_type, $object_id);
}
