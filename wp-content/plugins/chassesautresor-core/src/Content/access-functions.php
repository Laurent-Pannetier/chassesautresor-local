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

/**
 * Determine whether a user may view a riddle.
 */
function utilisateur_peut_voir_enigme(int $enigme_id, ?int $user_id = null): bool
{
    if (get_post_type($enigme_id) !== 'enigme') {
        return false;
    }

    $post_status = get_post_status($enigme_id);
    $system_status = get_field('enigme_cache_etat_systeme', $enigme_id);
    $user_id = $user_id ?? get_current_user_id();
    $hunt_id = recuperer_id_chasse_associee($enigme_id);
    $service = new ChassesAuTresor\Core\Content\RiddleAccessService();

    if (current_user_can('administrator')) {
        return $service->canView(true, false, false, '', '', '', false, false, false);
    }

    if (!$hunt_id) {
        return $service->canView(false, false, false, '', '', '', false, false, false);
    }

    $validation_status = get_field('chasse_cache_statut_validation', $hunt_id) ?? '';
    $is_hunt_finished = get_field('chasse_cache_statut', $hunt_id) === 'termine';
    if ($is_hunt_finished && $post_status === 'publish') {
        return $service->canView(false, true, true, 'publish', '', '', false, false, false);
    }

    $is_engaged = utilisateur_est_engage_dans_chasse($user_id, $hunt_id);
    $is_subscriber = is_user_logged_in() && in_array('abonne', wp_get_current_user()->roles, true);
    $is_organizer = (!$is_subscriber || $is_engaged)
        ? utilisateur_est_organisateur_associe_a_chasse($user_id, $hunt_id)
        : false;

    return $service->canView(
        false,
        true,
        $is_hunt_finished,
        (string) $post_status,
        (string) $system_status,
        (string) $validation_status,
        $is_organizer,
        $is_engaged,
        $is_subscriber
    );
}

/**
 * Determine whether a user may add a riddle to a hunt.
 */
function utilisateur_peut_ajouter_enigme(int $chasse_id, ?int $user_id = null): bool
{
    $user_id = $user_id ?? get_current_user_id();
    $is_hunt = get_post_type($chasse_id) === 'chasse';
    $is_authenticated = $user_id > 0 && is_user_logged_in();
    $is_organizer = $is_authenticated && est_organisateur($user_id);
    $is_associated = $is_organizer && $is_hunt
        && utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);
    $riddle_count = $is_associated ? count(recuperer_ids_enigmes_pour_chasse($chasse_id)) : 0;
    $service = new ChassesAuTresor\Core\Content\RiddleManagementService();

    return $service->canAdd(
        $is_hunt,
        $is_authenticated,
        $is_organizer,
        $is_hunt ? (string) get_post_status($chasse_id) : '',
        $is_hunt ? (string) get_field('chasse_cache_statut', $chasse_id) : '',
        $is_hunt ? (string) get_field('chasse_cache_statut_validation', $chasse_id) : '',
        $is_associated,
        $riddle_count
    );
}

/**
 * Determine whether a user may edit a riddle.
 */
function utilisateur_peut_modifier_enigme(int $enigme_id, ?int $user_id = null): bool
{
    $user_id = $user_id ?? get_current_user_id();
    $is_riddle = get_post_type($enigme_id) === 'enigme';
    $is_administrator = user_can($user_id, 'administrator');
    $hunt_id = $is_riddle && !$is_administrator ? (int) recuperer_id_chasse_associee($enigme_id) : 0;
    $has_hunt = $hunt_id > 0 && get_post_type($hunt_id) === 'chasse';
    $service = new ChassesAuTresor\Core\Content\RiddleManagementService();

    return $service->canEdit(
        $is_riddle,
        $is_administrator,
        $has_hunt,
        $has_hunt && utilisateur_est_organisateur_associe_a_chasse($user_id, $hunt_id)
    );
}

/**
 * Determine whether a user may delete a riddle.
 */
function utilisateur_peut_supprimer_enigme(int $enigme_id, ?int $user_id = null): bool
{
    $user_id = $user_id ?? get_current_user_id();
    $is_riddle = get_post_type($enigme_id) === 'enigme';
    $hunt_id = $is_riddle ? (int) recuperer_id_chasse_associee($enigme_id) : 0;
    $has_hunt = $hunt_id > 0 && get_post_type($hunt_id) === 'chasse';
    $is_authenticated = $user_id > 0;
    $is_organizer = $is_authenticated && est_organisateur($user_id);
    $is_associated = $is_organizer && $has_hunt
        && utilisateur_est_organisateur_associe_a_chasse($user_id, $hunt_id);
    $service = new ChassesAuTresor\Core\Content\RiddleManagementService();

    return $service->canDelete(
        $is_riddle,
        $is_authenticated,
        $is_organizer,
        $has_hunt,
        $has_hunt ? (string) get_field('chasse_cache_statut', $hunt_id) : '',
        $has_hunt ? (string) get_field('chasse_cache_statut_validation', $hunt_id) : '',
        $is_associated
    );
}
/**
 * Vérifie si un utilisateur peut ajouter une nouvelle chasse à un organisateur donné.
 *
 * @param int $organisateur_id
 * @return bool
 */
function utilisateur_peut_ajouter_chasse(int $organisateur_id): bool
{
    $service = new ChassesAuTresor\Core\Content\HuntManagementService();

    if (!is_user_logged_in()) {
        return $service->canCreate(false, false, false, false, false, false, false, false);
    }

    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    $user_id = (int) $user->ID;

    // Administrateur → pas d'ajout via l'interface publique
    if (user_can($user_id, 'manage_options')) {
        return $service->canCreate(true, true, false, false, false, false, false, false);
    }

    // L'utilisateur doit être lié à l'organisateur
    if (!utilisateur_peut_modifier_post($organisateur_id)) {
        return $service->canCreate(true, false, false, false, false, false, false, false);
    }

    $has_organizer_role = in_array(ROLE_ORGANISATEUR, $roles, true);
    $has_creation_role = in_array(ROLE_ORGANISATEUR_CREATION, $roles, true);
    $is_organizer_published = $has_organizer_role && get_post_status($organisateur_id) === 'publish';

    return $service->canCreate(
        true,
        false,
        true,
        $has_organizer_role,
        $has_creation_role,
        $is_organizer_published,
        $is_organizer_published && organisateur_a_chasse_pending($organisateur_id),
        !$has_organizer_role && $has_creation_role && organisateur_a_des_chasses($organisateur_id)
    );
}

/**
 * Détermine si l'utilisateur peut afficher le panneau d'édition d'un post.
 *
 * Cette vérification repose sur la relation organisateur ↔ utilisateur et
 * sur différents statuts des CPT.
 *
 * @param int $post_id ID du post concerné.
 * @return bool True si le panneau peut être affiché.
 */
function utilisateur_peut_voir_panneau(int $post_id): bool
{
    $is_authenticated = is_user_logged_in();
    $is_administrator = $is_authenticated && current_user_can('manage_options');
    $user = $is_authenticated ? wp_get_current_user() : null;
    $is_organizer = $user !== null && !$is_administrator && est_organisateur($user->ID);
    $can_modify_content = $is_administrator || ($is_organizer && utilisateur_peut_modifier_post($post_id));
    $content_type = $is_authenticated ? (string) get_post_type($post_id) : '';
    $service = new ChassesAuTresor\Core\Content\ContentPanelAccessService();

    return $service->canView(
        $is_authenticated,
        $is_administrator,
        $is_organizer,
        $can_modify_content,
        $content_type,
        $is_authenticated ? (string) get_post_status($post_id) : '',
        $content_type === 'chasse' ? (string) get_field('chasse_cache_statut_validation', $post_id) : '',
        $content_type === 'enigme' ? (string) get_field('enigme_cache_etat_systeme', $post_id) : ''
    );
}

/**
 * Détermine si l'utilisateur peut éditer les champs désactivés d'un post.
 *
 * Les conditions incluent celles de `utilisateur_peut_voir_panneau()` et des
 * statuts métiers plus stricts selon le type de contenu.
 *
 * @param int $post_id ID du post concerné.
 * @return bool True si l'édition avancée est autorisée.
 */
function utilisateur_peut_editer_champs(int $post_id): bool
{
    $can_view_panel = utilisateur_peut_voir_panneau($post_id);
    $is_administrator = $can_view_panel && current_user_can('manage_options');
    $content_type = $can_view_panel && !$is_administrator ? (string) get_post_type($post_id) : '';
    $hunt_id = $content_type === 'enigme' ? (int) recuperer_id_chasse_associee($post_id) : 0;
    $has_hunt = $hunt_id > 0;
    $status_source_id = $content_type === 'enigme' ? $hunt_id : $post_id;
    $has_hunt_status = $content_type === 'chasse' || ($content_type === 'enigme' && $has_hunt);
    $can_modify_content = $content_type === 'organisateur'
        && !$is_administrator
        && utilisateur_peut_modifier_post($post_id);
    $system_status = '';
    if ($content_type === 'enigme') {
        $system_status = (string) get_field('enigme_cache_etat_systeme', $post_id);
    } elseif ($content_type === 'indice') {
        $system_status = (string) get_field('indice_cache_etat_systeme', $post_id);
    }
    $service = new ChassesAuTresor\Core\Content\ContentFieldAccessService();

    return $service->canEdit(
        $can_view_panel,
        $is_administrator,
        $can_modify_content,
        $content_type,
        $can_view_panel ? (string) get_post_status($post_id) : '',
        $has_hunt_status ? (string) get_field('chasse_cache_statut_validation', $status_source_id) : '',
        $has_hunt_status ? (string) get_field('chasse_cache_statut', $status_source_id) : '',
        $system_status,
        $has_hunt,
        $has_hunt ? (string) get_post_status($hunt_id) : ''
    );
}


/**
 * Vérifie si un champ donné est éditable pour un utilisateur donné sur un post donné.
 *
 * @param string $champ Nom du champ ACF ou champ natif (ex : post_title)
 * @param int $post_id ID du post (CPT organisateur, chasse, etc.)
 * @param int|null $user_id ID utilisateur (par défaut : utilisateur connecté)
 * @return bool True si le champ est éditable, False sinon
 */
function champ_est_editable($champ, $post_id, $user_id = null)
{
    $has_valid_context = (bool) $post_id && is_user_logged_in();
    $is_administrator = $has_valid_context && current_user_can('manage_options');
    $post_type = $has_valid_context && !$is_administrator ? (string) get_post_type($post_id) : '';
    $is_organizer_title = $post_type === 'organisateur' && $champ === 'post_title';
    $roles = $has_valid_context && !$is_administrator ? (array) wp_get_current_user()->roles : [];
    $requires_advanced_access = $post_type === 'indice'
        || ($post_type === 'enigme' && $champ === 'post_title')
        || ($post_type === 'chasse'
            && in_array($champ, ['post_title', 'caracteristiques.chasse_infos_cout_points'], true));
    $can_edit_advanced_fields = $requires_advanced_access && utilisateur_peut_editer_champs($post_id);
    $hunt_count = 0;
    $creation_hunt_count = 0;

    if ($is_organizer_title && in_array(ROLE_ORGANISATEUR_CREATION, $roles, true)) {
        $hunts_query = get_chasses_de_organisateur($post_id);
        $hunt_count = is_a($hunts_query, 'WP_Query') ? (int) $hunts_query->post_count : 0;
        $creation_hunt_count = $hunt_count === 1 ? count(get_chasses_en_creation($post_id)) : 0;
    }

    $service = new ChassesAuTresor\Core\Content\ContentFieldPolicyService();

    return $service->canEdit(
        $has_valid_context,
        $is_administrator,
        $has_valid_context && ($is_administrator || utilisateur_peut_modifier_post($post_id)),
        $post_type,
        (string) $champ,
        $can_edit_advanced_fields,
        $has_valid_context && !$is_administrator ? (string) get_post_status($post_id) : '',
        in_array(ROLE_ORGANISATEUR_CREATION, $roles, true),
        $hunt_count,
        $creation_hunt_count
    );
}


/**
 * Vérifie si un utilisateur a le droit de consulter la solution (PDF ou texte) d'une énigme
 *
 * @param int $post_id ID du post (énigme ou chasse)
 * @param int $user_id ID de l'utilisateur connecté
 * @return bool
 */
function utilisateur_peut_voir_solution_enigme(int $post_id, int $user_id): bool
{
    if (!$post_id || !$user_id) {
        return false;
    }

    $type = get_post_type($post_id);
    if ($type === 'chasse') {
        return utilisateur_peut_voir_solution_chasse($post_id, $user_id);
    }

    if ($type !== 'enigme') {
        return false;
    }

    $solution = solution_recuperer_par_objet($post_id, 'enigme');
    if (!$solution) {
        return false;
    }

    $service = new ChassesAuTresor\Core\Content\SolutionAccessService();
    if (user_can($user_id, 'manage_options')) {
        return $service->canViewRiddleSolution(true, false, false, false, false, '');
    }

    $chasse_id = recuperer_id_chasse_associee($post_id);
    $is_hunt_finished = $chasse_id && get_field('chasse_cache_statut', $chasse_id) === 'termine';
    $is_engaged = $is_hunt_finished
        && function_exists('utilisateur_est_engage_dans_enigme')
        && utilisateur_est_engage_dans_enigme($user_id, $post_id);
    $is_organizer = !$is_engaged
        && $chasse_id
        && utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);
    $riddle_status = !$chasse_id || $is_engaged || $is_organizer
        ? ''
        : (string) get_statut_utilisateur_enigme($user_id, $post_id);

    return $service->canViewRiddleSolution(
        false,
        (bool) $chasse_id,
        $is_hunt_finished,
        $is_engaged,
        $is_organizer,
        $riddle_status
    );
}

/**
 * Vérifie si un utilisateur a le droit de consulter la solution d'une chasse.
 *
 * @param int $chasse_id ID de la chasse
 * @param int $user_id   ID de l'utilisateur connecté
 * @return bool
 */
function utilisateur_peut_voir_solution_chasse(int $chasse_id, int $user_id): bool
{
    if (!$chasse_id) {
        return false;
    }

    $solution = solution_recuperer_par_objet($chasse_id, 'chasse');
    if (!$solution) {
        return false;
    }

    $service = new ChassesAuTresor\Core\Content\SolutionAccessService();
    if ($user_id > 0 && user_can($user_id, 'manage_options')) {
        return $service->canViewHuntSolution(true, true, false, false);
    }

    $is_organizer = $user_id > 0
        && utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);

    return $service->canViewHuntSolution(
        $user_id > 0,
        false,
        $is_organizer,
        $user_id > 0 && !$is_organizer && utilisateur_est_engage_dans_chasse($user_id, $chasse_id)
    );
}


/**
 * @hook wp_ajax_verifier_et_enregistrer_condition_pre_requis
 * @return void (JSON)
 */
function verifier_et_enregistrer_condition_pre_requis()
{
    ChassesAuTresor\Core\Content\RiddlePrerequisiteAjaxHandler::handle();
}



/**
 * Détermine si une chasse doit être visible pour un utilisateur.
 *
 * Règles de visibilité :
 * - Si statut WP = 'publish' ET 'chasse_cache_statut_validation' = 'valide'
 *     → visible par tous les utilisateurs (y compris anonymes)
 * - Si statut WP = 'pending'
 *     → visible uniquement si user est admin OU lié à la chasse
 * - Tous les autres cas → invisible
 *
 * @param int $chasse_id ID de la chasse.
 * @param int $user_id   ID de l'utilisateur.
 * @return bool          True si visible, false sinon.
 */
function chasse_est_visible_pour_utilisateur(int $chasse_id, int $user_id): bool
{
    $publication_status = (string) get_post_status($chasse_id);
    $is_pending = $publication_status === 'pending';
    $is_administrator = $is_pending && user_can($user_id, 'manage_options');
    $service = new ChassesAuTresor\Core\Content\HuntAccessService();

    return $service->canView(
        $publication_status,
        (string) get_field('chasse_cache_statut_validation', $chasse_id),
        $is_administrator,
        $is_pending
            && !$is_administrator
            && utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    );
}
