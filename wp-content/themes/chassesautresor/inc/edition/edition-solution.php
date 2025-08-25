<?php
/**
 * Gestion de la publication différée des solutions.
 *
 * @package chassesautresor.com
 */

/**
 * Planifie la publication d'une solution.
 *
 * Calcule la date cible selon les champs ACF puis programme un événement cron
 * unique pour rendre la solution accessible.
 *
 * @param int $solution_id ID de la solution.
 * @return void
 */
function solution_planifier_publication(int $solution_id): void
{
    if (get_post_type($solution_id) !== 'solution') {
        return;
    }

    $cible = get_field('solution_cible_type', $solution_id);
    $chasse_id = 0;
    if ($cible === 'chasse') {
        $chasse = get_field('solution_chasse_linked', $solution_id);
        $chasse_id = is_array($chasse) ? (int) ($chasse[0] ?? 0) : (int) $chasse;
    } elseif ($cible === 'enigme') {
        $enigme = get_field('solution_enigme_linked', $solution_id);
        $enigme_id = is_array($enigme) ? (int) ($enigme[0] ?? 0) : (int) $enigme;
        $chasse_id = $enigme_id ? recuperer_id_chasse_associee($enigme_id) : 0;
    }

    if (!$chasse_id) {
        return;
    }

    $statut = get_field('statut_chasse', $chasse_id);
    $terminee = is_string($statut) && in_array(strtolower($statut), ['terminée', 'termine', 'terminé'], true);
    if (!$terminee) {
        return;
    }

    $dispo = get_field('solution_disponibilite', $solution_id) ?: 'fin_chasse';
    $decalage = (int) get_field('solution_decalage_jours', $solution_id);
    $heure = get_field('solution_heure_publication', $solution_id) ?: '00:00';

    $base = current_time('timestamp');
    $timestamp = $base;
    if ($dispo === 'differee') {
        $timestamp = strtotime("+{$decalage} days {$heure}", $base);
    }

    wp_clear_scheduled_hook('publier_solution_programmee', [$solution_id]);

    if (!$timestamp || $timestamp <= current_time('timestamp')) {
        solution_rendre_accessible($solution_id);
        return;
    }

    update_post_meta($solution_id, 'solution_date_disponibilite', gmdate('Y-m-d H:i:s', $timestamp));
    update_field('solution_cache_etat_systeme', 'programme', $solution_id);
    wp_schedule_single_event($timestamp, 'publier_solution_programmee', [$solution_id]);
}

/**
 * Rend une solution accessible immédiatement.
 *
 * @param int $solution_id ID de la solution.
 * @return void
 */
function solution_rendre_accessible(int $solution_id): void
{
    if (get_post_type($solution_id) !== 'solution') {
        return;
    }

    update_field('solution_cache_etat_systeme', 'accessible', $solution_id);
    delete_post_meta($solution_id, 'solution_date_disponibilite');
    if (get_post_status($solution_id) !== 'publish') {
        wp_update_post(['ID' => $solution_id, 'post_status' => 'publish']);
    }
}
add_action('publier_solution_programmee', 'solution_rendre_accessible');

/**
 * Basculer les solutions programmées dont la date est atteinte.
 *
 * @return void
 */
function basculer_solutions_programme(): void
{
    $solutions = get_posts([
        'post_type'      => 'solution',
        'post_status'    => ['publish', 'pending', 'draft', 'private', 'future'],
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'   => 'solution_cache_etat_systeme',
                'value' => 'programme',
            ],
            [
                'key'     => 'solution_date_disponibilite',
                'value'   => current_time('mysql'),
                'compare' => '<=',
                'type'    => 'DATETIME',
            ],
        ],
    ]);

    foreach ($solutions as $sid) {
        solution_rendre_accessible($sid);
    }
}
add_action('basculer_solutions_programme', 'basculer_solutions_programme');

/**
 * Planifie la tâche récurrente de basculement des solutions.
 *
 * @return void
 */
function planifier_tache_basculer_solutions_programme(): void
{
    if (!wp_next_scheduled('basculer_solutions_programme')) {
        wp_schedule_event(time(), 'hourly', 'basculer_solutions_programme');
    }
}
add_action('after_switch_theme', 'planifier_tache_basculer_solutions_programme');

/**
 * Hook ACF pour planifier la publication à la sauvegarde.
 *
 * @param int $post_id ID du post sauvegardé.
 * @return void
 */
function solution_acf_save_post(int $post_id): void
{
    if (get_post_type($post_id) !== 'solution') {
        return;
    }

    solution_planifier_publication($post_id);
    reordonner_solutions_pour_solution($post_id);
}
add_action('acf/save_post', 'solution_acf_save_post', 40);

// ==================================================
// 💡 GESTION DES SOLUTIONS (création, redirection, AJAX)
// ==================================================

/**
 * Redirige l’affichage d’une solution vers sa chasse ou son énigme liée.
 *
 * @return void
 */
function rediriger_si_affichage_solution(): void
{
    if (!is_singular('solution')) {
        return;
    }

    $solution_id = get_the_ID();
    $cible_type  = get_field('solution_cible_type', $solution_id);
    $redirect_id = 0;

    if ($cible_type === 'chasse') {
        $redirect_id = (int) get_field('solution_chasse_linked', $solution_id);
    } elseif ($cible_type === 'enigme') {
        $redirect_id = (int) get_field('solution_enigme_linked', $solution_id);
    }

    if ($redirect_id) {
        wp_safe_redirect(get_permalink($redirect_id));
        exit;
    }
}
add_action('template_redirect', 'rediriger_si_affichage_solution');

/**
 * Calcule le rang de la prochaine solution pour une chasse ou une énigme.
 *
 * @param int    $objet_id   ID de la chasse ou de l’énigme.
 * @param string $objet_type Type de cible ('chasse' ou 'enigme').
 * @return int
 */
function prochain_rang_solution(int $objet_id, string $objet_type): int
{
    if (!in_array($objet_type, ['chasse', 'enigme'], true)) {
        return 1;
    }

    if ($objet_type === 'chasse') {
        $meta_query = [
            [
                'key'   => 'solution_cible_type',
                'value' => 'chasse',
            ],
            [
                'key'   => 'solution_chasse_linked',
                'value' => $objet_id,
            ],
            [
                'key'     => 'solution_cache_etat_systeme',
                'value'   => ['programme', 'accessible', 'desactive'],
                'compare' => 'IN',
            ],
        ];
    } else {
        $meta_query = [
            [
                'key'   => 'solution_cible_type',
                'value' => 'enigme',
            ],
            [
                'key'   => 'solution_enigme_linked',
                'value' => $objet_id,
            ],
            [
                'key'     => 'solution_cache_etat_systeme',
                'value'   => ['programme', 'accessible', 'desactive'],
                'compare' => 'IN',
            ],
        ];
    }

    $existing = function_exists('get_posts')
        ? get_posts([
            'post_type'      => 'solution',
            'post_status'    => ['publish', 'pending', 'draft', 'private', 'future'],
            'meta_query'     => $meta_query,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'posts_per_page' => -1,
        ])
        : [];

    return count($existing) + 1;
}

/**
 * Renomme les solutions d'une chasse ou d'une énigme en séquence.
 *
 * @param int    $objet_id   ID de la chasse ou de l'énigme.
 * @param string $objet_type Type de cible ('chasse' ou 'enigme').
 * @return void
 */
function reordonner_solutions(int $objet_id, string $objet_type): void
{
    static $processing = false;
    if (!in_array($objet_type, ['chasse', 'enigme'], true) || $processing) {
        return;
    }

    $processing = true;

    if ($objet_type === 'chasse') {
        $meta_query = [
            [
                'key'   => 'solution_cible_type',
                'value' => 'chasse',
            ],
            [
                'key'   => 'solution_chasse_linked',
                'value' => $objet_id,
            ],
            [
                'key'     => 'solution_cache_etat_systeme',
                'value'   => ['programme', 'accessible', 'desactive'],
                'compare' => 'IN',
            ],
        ];
    } else {
        $meta_query = [
            [
                'key'   => 'solution_cible_type',
                'value' => 'enigme',
            ],
            [
                'key'   => 'solution_enigme_linked',
                'value' => $objet_id,
            ],
            [
                'key'     => 'solution_cache_etat_systeme',
                'value'   => ['programme', 'accessible', 'desactive'],
                'compare' => 'IN',
            ],
        ];
    }

    $solutions = get_posts([
        'post_type'      => 'solution',
        'post_status'    => ['publish', 'pending', 'draft', 'private', 'future'],
        'meta_query'     => $meta_query,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'posts_per_page' => -1,
    ]);

    $objet_titre = get_the_title($objet_id);
    $i          = 1;
    foreach ($solutions as $sid) {
        $title = sprintf(__('Solution %s #%d', 'chassesautresor-com'), $objet_titre, $i);
        wp_update_post([
            'ID'         => $sid,
            'post_title' => $title,
        ]);
        $i++;
    }

    $processing = false;
}

/**
 * Renomme les solutions liées à la solution donnée.
 *
 * @param int $solution_id ID de la solution.
 * @return void
 */
function reordonner_solutions_pour_solution(int $solution_id): void
{
    $type = get_field('solution_cible_type', $solution_id);
    if ($type === 'chasse') {
        $objet_id = (int) get_field('solution_chasse_linked', $solution_id);
    } elseif ($type === 'enigme') {
        $objet_id = (int) get_field('solution_enigme_linked', $solution_id);
    } else {
        return;
    }

    if ($objet_id) {
        reordonner_solutions($objet_id, $type);
    }
}

/**
 * Réordonne les solutions après sauvegarde d'une solution.
 *
 * @param int $post_id ID de la solution.
 * @return void
 */
function reordonner_solutions_apres_enregistrement(int $post_id): void
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    reordonner_solutions_pour_solution($post_id);
}

add_action('save_post_solution', 'reordonner_solutions_apres_enregistrement', 20, 1);

/**
 * Enregistre la cible d'une solution avant suppression définitive.
 *
 * @param int $post_id ID du post.
 * @return void
 */
function memoriser_cible_solution_avant_suppression(int $post_id): void
{
    global $solution_delete_context;

    if (get_post_type($post_id) !== 'solution') {
        return;
    }

    $type = get_field('solution_cible_type', $post_id);
    if ($type === 'chasse') {
        $id = (int) get_field('solution_chasse_linked', $post_id);
    } elseif ($type === 'enigme') {
        $id = (int) get_field('solution_enigme_linked', $post_id);
    } else {
        $id = 0;
    }

    if ($id) {
        $solution_delete_context = ['id' => $id, 'type' => $type];
    }
}

add_action('before_delete_post', 'memoriser_cible_solution_avant_suppression');

/**
 * Réordonne les solutions après suppression définitive.
 *
 * @param int $post_id ID du post.
 * @return void
 */
function reordonner_solutions_apres_suppression(int $post_id): void
{
    global $solution_delete_context;

    if ($solution_delete_context) {
        reordonner_solutions($solution_delete_context['id'], $solution_delete_context['type']);
        $solution_delete_context = null;
    }
}

add_action('deleted_post', 'reordonner_solutions_apres_suppression');
add_action('trashed_post', 'reordonner_solutions_pour_solution');

/**
 * Crée une solution liée à une chasse ou une énigme.
 *
 * @param int      $objet_id   ID de la chasse ou de l’énigme.
 * @param string   $objet_type Type de cible ('chasse' ou 'enigme').
 * @param int|null $user_id    ID utilisateur (null = courant).
 * @return int|WP_Error
 */
function creer_solution_pour_objet(int $objet_id, string $objet_type, ?int $user_id = null)
{
    if (!in_array($objet_type, ['chasse', 'enigme'], true)) {
        return new WP_Error('type_invalide', __('Type de cible invalide.', 'chassesautresor-com'));
    }

    if (get_post_type($objet_id) !== $objet_type) {
        return new WP_Error('cible_invalide', __('ID cible invalide.', 'chassesautresor-com'));
    }

    if (!is_user_logged_in()) {
        return new WP_Error('non_connecte', __('Utilisateur non connecté.', 'chassesautresor-com'));
    }

    if (!solution_action_autorisee('create', $objet_type, $objet_id)) {
        return new WP_Error('permission_refusee', __('Droits insuffisants.', 'chassesautresor-com'));
    }

    $chasse_id = $objet_type === 'chasse'
        ? $objet_id
        : recuperer_id_chasse_associee($objet_id);

    if (!$chasse_id) {
        return new WP_Error('permission_refusee', __('Droits insuffisants.', 'chassesautresor-com'));
    }

    $user_id       = $user_id ?? get_current_user_id();
    $solution_rank = prochain_rang_solution($objet_id, $objet_type);

    $solution_id = wp_insert_post([
        'post_type'   => 'solution',
        'post_status' => 'pending',
        'post_title'  => TITRE_DEFAUT_SOLUTION,
        'post_author' => $user_id,
    ]);

    if (is_wp_error($solution_id)) {
        return $solution_id;
    }

    $objet_titre   = get_the_title($objet_id);
    $nouveau_titre = sprintf(__('Solution %s #%d', 'chassesautresor-com'), $objet_titre, $solution_rank);
    wp_update_post([
        'ID'         => $solution_id,
        'post_title' => $nouveau_titre,
    ]);

    update_field('solution_cible_type', $objet_type, $solution_id);
    update_field('solution_chasse_linked', $chasse_id, $solution_id);
    if ($objet_type === 'enigme') {
        update_field('solution_enigme_linked', $objet_id, $solution_id);
    }
    update_field('solution_disponibilite', 'fin_chasse', $solution_id);
    update_field('solution_decalage_jours', 0, $solution_id);
    update_field('solution_heure_publication', '00:00', $solution_id);
    update_field('solution_cache_etat_systeme', 'desactive', $solution_id);

    reordonner_solutions($objet_id, $objet_type);

    return $solution_id;
}

/**
 * Enregistre l’URL personnalisée /creer-solution/
 *
 * @return void
 */
function register_endpoint_creer_solution(): void
{
    add_rewrite_rule('^creer-solution/?$', 'index.php?creer_solution=1', 'top');
    add_rewrite_tag('%creer_solution%', '1');
}
add_action('init', 'register_endpoint_creer_solution');

/**
 * S'assure que les règles de réécriture prennent en compte /creer-solution/.
 *
 * @return void
 */
function flush_rewrite_rules_creer_solution(): void
{
    register_endpoint_creer_solution();
    flush_rewrite_rules();
    update_option('creer_solution_rewrite_flushed', 1);
}

add_action('after_switch_theme', 'flush_rewrite_rules_creer_solution');

add_action('init', function (): void {
    if (!get_option('creer_solution_rewrite_flushed')) {
        flush_rewrite_rules_creer_solution();
    }
}, 20);

/**
 * Détecte l’appel à /creer-solution/ et redirige vers la page cible.
 *
 * @return void
 */
function creer_solution_et_rediriger_si_appel(): void
{
    if (get_query_var('creer_solution') !== '1') {
        return;
    }

    $nonce = $_GET['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'creer_solution')) {
        wp_die(__('Action non autorisée.', 'chassesautresor-com'), 'Erreur', ['response' => 403]);
    }

    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }

    $cible_id   = isset($_GET['chasse_id']) ? absint($_GET['chasse_id']) : 0;
    $cible_type = 'chasse';
    if (!$cible_id) {
        $cible_id   = isset($_GET['enigme_id']) ? absint($_GET['enigme_id']) : 0;
        $cible_type = 'enigme';
    }

    if (!$cible_id) {
        wp_die(__('ID cible manquant.', 'chassesautresor-com'), 'Erreur', ['response' => 400]);
    }

    $solution_id = creer_solution_pour_objet($cible_id, $cible_type);
    if (is_wp_error($solution_id)) {
        $error_message = sanitize_text_field($solution_id->get_error_message());
        $referer       = wp_get_referer() ?: get_permalink($cible_id);
        $redirect_url  = add_query_arg('erreur', $error_message, $referer);
        wp_safe_redirect($redirect_url);
        exit;
    }

    wp_safe_redirect(get_permalink($cible_id));
    exit;
}
add_action('template_redirect', 'creer_solution_et_rediriger_si_appel');

/**
 * Liste les solutions via AJAX pour un objet donné.
 *
 * @return void
 */
function ajax_solutions_lister_table(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $objet_id   = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
    $objet_type = sanitize_key($_POST['objet_type'] ?? '');
    $page       = isset($_POST['page']) ? (int) $_POST['page'] : 1;

    if (!$objet_id || !in_array($objet_type, ['chasse', 'enigme'], true)
        || get_post_type($objet_id) !== $objet_type
    ) {
        wp_send_json_error('post_invalide');
    }

    if (!solution_action_autorisee('edit', $objet_type, $objet_id)) {
        wp_send_json_error('acces_refuse');
    }

    $per_page = 5;
    if ($objet_type === 'chasse') {
        $enigme_ids = recuperer_ids_enigmes_pour_chasse($objet_id);
        $meta       = [
            'relation' => 'OR',
            [
                'relation' => 'AND',
                [
                    'key'   => 'solution_cible_type',
                    'value' => 'chasse',
                ],
                [
                    'key'   => 'solution_chasse_linked',
                    'value' => $objet_id,
                ],
            ],
        ];
        if (!empty($enigme_ids)) {
            $meta[] = [
                'relation' => 'AND',
                [
                    'key'   => 'solution_cible_type',
                    'value' => 'enigme',
                ],
                [
                    'key'     => 'solution_enigme_linked',
                    'value'   => $enigme_ids,
                    'compare' => 'IN',
                ],
            ];
        }
    } else {
        $meta = [
            [
                'key'   => 'solution_cible_type',
                'value' => 'enigme',
            ],
            [
                'key'   => 'solution_enigme_linked',
                'value' => $objet_id,
            ],
        ];
    }

    $query = new WP_Query([
        'post_type'      => 'solution',
        'post_status'    => ['publish', 'pending', 'draft'],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'posts_per_page' => $per_page,
        'paged'          => max(1, $page),
        'meta_query'     => $meta,
    ]);

    ob_start();
    get_template_part('template-parts/common/solutions-table', null, [
        'solutions'  => $query->posts,
        'page'       => max(1, $page),
        'pages'      => (int) $query->max_num_pages,
        'objet_type' => $objet_type,
        'objet_id'   => $objet_id,
    ]);
    $html = ob_get_clean();

    wp_send_json_success([
        'html'  => $html,
        'page'  => max(1, $page),
        'pages' => (int) $query->max_num_pages,
    ]);
}
add_action('wp_ajax_solutions_lister_table', 'ajax_solutions_lister_table');

/**
 * Crée une solution via une requête AJAX depuis une modale.
 *
 * @return void
 */
function ajax_creer_solution_modal(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $objet_id   = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
    $objet_type = sanitize_key($_POST['objet_type'] ?? '');

    if (!$objet_id || !in_array($objet_type, ['chasse', 'enigme'], true) || get_post_type($objet_id) !== $objet_type) {
        wp_send_json_error('post_invalide');
    }

    if ($objet_type === 'enigme') {
        $linked = isset($_POST['solution_enigme_linked']) ? (int) $_POST['solution_enigme_linked'] : 0;
        if (!$linked || $linked !== $objet_id) {
            wp_send_json_error('post_invalide');
        }
    }

    if (!solution_action_autorisee('create', $objet_type, $objet_id)) {
        wp_send_json_error('acces_refuse');
    }

    $has_file   = !empty($_FILES['solution_fichier']['tmp_name']) || !empty($_POST['solution_fichier']);
    $has_explic = isset($_POST['solution_explication']) && trim((string) $_POST['solution_explication']) !== '';
    if (!$has_file && !$has_explic) {
        wp_send_json_error('contenu_manquant');
    }

    $solution_id = creer_solution_pour_objet($objet_id, $objet_type);
    if (is_wp_error($solution_id)) {
        wp_send_json_error($solution_id->get_error_message());
    }

    $fichier = 0;
    if (!empty($_FILES['solution_fichier']) && !empty($_FILES['solution_fichier']['tmp_name'])) {
        if (!function_exists('media_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $fichier = media_handle_upload('solution_fichier', $solution_id);
        if (is_wp_error($fichier)) {
            wp_send_json_error($fichier->get_error_message());
        }
    } elseif (isset($_POST['solution_fichier'])) {
        $fichier = (int) $_POST['solution_fichier'];
    }

    $explic = wp_kses_post($_POST['solution_explication'] ?? '');
    $dispo  = sanitize_key($_POST['solution_disponibilite'] ?? 'fin_chasse');
    $delai  = isset($_POST['solution_decalage_jours']) ? (int) $_POST['solution_decalage_jours'] : 0;
    $heure  = sanitize_text_field($_POST['solution_heure_publication'] ?? '');

    if ($fichier) {
        update_field('solution_fichier', $fichier, $solution_id);
    }
    if ($explic !== '') {
        update_field('solution_explication', $explic, $solution_id);
    }

    $dispo = $dispo === 'differee' ? 'differee' : 'fin_chasse';
    update_field('solution_disponibilite', $dispo, $solution_id);
    update_field('solution_decalage_jours', $delai, $solution_id);
    update_field('solution_heure_publication', $heure ?: '00:00', $solution_id);

    solution_planifier_publication($solution_id);

    wp_send_json_success(['solution_id' => $solution_id]);
}
add_action('wp_ajax_creer_solution_modal', 'ajax_creer_solution_modal');

/**
 * Met à jour une solution existante via le modal d'édition.
 *
 * @return void
 */
function ajax_modifier_solution_modal(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $solution_id = isset($_POST['solution_id']) ? (int) $_POST['solution_id'] : 0;
    $objet_id    = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
    $objet_type  = sanitize_key($_POST['objet_type'] ?? '');

    if (!$solution_id || get_post_type($solution_id) !== 'solution') {
        wp_send_json_error('solution_invalide');
    }
    if (!$objet_id || !in_array($objet_type, ['chasse', 'enigme'], true) || get_post_type($objet_id) !== $objet_type) {
        wp_send_json_error('post_invalide');
    }
    if (!solution_action_autorisee('edit', $objet_type, $objet_id)) {
        wp_send_json_error('acces_refuse');
    }

    $has_file   = !empty($_FILES['solution_fichier']['tmp_name']) || !empty($_POST['solution_fichier']);
    $has_explic = isset($_POST['solution_explication']) && trim((string) $_POST['solution_explication']) !== '';
    if (!$has_file && !$has_explic) {
        wp_send_json_error('contenu_manquant');
    }

    $fichier = 0;
    $has_file_input = isset($_POST['solution_fichier']) || (!empty($_FILES['solution_fichier']) && !empty($_FILES['solution_fichier']['tmp_name']));
    if (!empty($_FILES['solution_fichier']) && !empty($_FILES['solution_fichier']['tmp_name'])) {
        if (!function_exists('media_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $fichier = media_handle_upload('solution_fichier', $solution_id);
        if (is_wp_error($fichier)) {
            wp_send_json_error($fichier->get_error_message());
        }
    } elseif (isset($_POST['solution_fichier'])) {
        $fichier = (int) $_POST['solution_fichier'];
    }

    $explic = wp_kses_post($_POST['solution_explication'] ?? '');
    $dispo  = sanitize_key($_POST['solution_disponibilite'] ?? 'fin_chasse');
    $delai  = isset($_POST['solution_decalage_jours']) ? (int) $_POST['solution_decalage_jours'] : 0;
    $heure  = sanitize_text_field($_POST['solution_heure_publication'] ?? '');

    if ($fichier) {
        update_field('solution_fichier', $fichier, $solution_id);
    } elseif ($has_file_input) {
        delete_field('solution_fichier', $solution_id);
    }
    update_field('solution_explication', $explic, $solution_id);

    $dispo = $dispo === 'differee' ? 'differee' : 'fin_chasse';
    update_field('solution_disponibilite', $dispo, $solution_id);
    update_field('solution_decalage_jours', $delai, $solution_id);
    update_field('solution_heure_publication', $heure ?: '00:00', $solution_id);

    solution_planifier_publication($solution_id);

    wp_send_json_success(['solution_id' => $solution_id]);
}
add_action('wp_ajax_modifier_solution_modal', 'ajax_modifier_solution_modal');
