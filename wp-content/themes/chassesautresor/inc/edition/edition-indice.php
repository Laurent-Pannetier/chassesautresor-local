<?php
defined('ABSPATH') || exit;

// ==================================================
// 💡 CRÉATION & ÉDITION D’UN INDICE
// ==================================================
// 🔹 enqueue_script_indice_edit() → Charge JS sur single indice
// 🔹 register_endpoint_creer_indice() → Enregistre /creer-indice
// 🔹 creer_indice_pour_objet() → Crée un indice lié à une chasse ou une énigme
// 🔹 creer_indice_et_rediriger_si_appel() → Crée un indice et redirige

/**
 * Charge les scripts nécessaires à l’édition d’un indice.
 *
 * @return void
 */
function enqueue_script_indice_edit(): void
{
    if (!is_singular('indice')) {
        return;
    }

    $indice_id = get_the_ID();
    if (!utilisateur_peut_modifier_post($indice_id)) {
        return;
    }

    enqueue_core_edit_scripts(['organisateur-edit', 'indice-edit']);
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'enqueue_script_indice_edit');

/**
 * Crée un indice lié à une chasse ou une énigme.
 *
 * @param int      $objet_id   ID de la chasse ou de l’énigme.
 * @param string   $objet_type Type de cible ('chasse' ou 'enigme').
 * @param int|null $user_id    ID utilisateur (null = courant).
 * @return int|WP_Error
 */
function creer_indice_pour_objet(int $objet_id, string $objet_type, ?int $user_id = null)
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

    if (!utilisateur_peut_modifier_post($objet_id)) {
        return new WP_Error('permission_refusee', __('Droits insuffisants.', 'chassesautresor-com'));
    }

    $chasse_id = $objet_type === 'chasse'
        ? $objet_id
        : recuperer_id_chasse_associee($objet_id);

    if (!$chasse_id || !utilisateur_peut_modifier_post($chasse_id)) {
        return new WP_Error('permission_refusee', __('Droits insuffisants.', 'chassesautresor-com'));
    }

    $user_id = $user_id ?? get_current_user_id();

    $indice_id = wp_insert_post([
        'post_type'   => 'indice',
        'post_status' => 'pending',
        'post_title'  => TITRE_DEFAUT_INDICE,
        'post_author' => $user_id,
    ]);

    if (is_wp_error($indice_id)) {
        return $indice_id;
    }

    $titre_objet   = get_the_title($objet_id);
    $nouveau_titre = sprintf(__('Indice #%d - %s', 'chassesautresor-com'), $indice_id, $titre_objet);
    wp_update_post([
        'ID'         => $indice_id,
        'post_title' => $nouveau_titre,
    ]);

    update_field('indice_cible', $objet_type, $indice_id);
    update_field('indice_cible_objet', $objet_id, $indice_id);
    update_field('indice_chasse_linked', $chasse_id, $indice_id);
    update_field('indice_disponibilite', 'immediate', $indice_id);

    $date_disponibilite = wp_date('Y-m-d H:i:s', (int) current_time('timestamp') + DAY_IN_SECONDS);
    update_field('indice_date_disponibilite', $date_disponibilite, $indice_id);

    update_field('indice_cout_points', 0, $indice_id);
    update_field('indice_cache_complet', false, $indice_id);

    return $indice_id;
}

/**
 * Enregistre l’URL personnalisée /creer-indice/
 *
 * @return void
 */
function register_endpoint_creer_indice(): void
{
    add_rewrite_rule('^creer-indice/?$', 'index.php?creer_indice=1', 'top');
    add_rewrite_tag('%creer_indice%', '1');
}
add_action('init', 'register_endpoint_creer_indice');

/**
 * S'assure que les règles de réécriture prennent en compte /creer-indice/.
 *
 * Cette fonction est exécutée lors de l'activation du thème ou
 * automatiquement une fois si les règles n'ont pas encore été mises à jour.
 *
 * @return void
 */
function flush_rewrite_rules_creer_indice(): void
{
    register_endpoint_creer_indice();
    flush_rewrite_rules();
    update_option('creer_indice_rewrite_flushed', 1);
}

add_action('after_switch_theme', 'flush_rewrite_rules_creer_indice');

add_action('init', function (): void {
    if (!get_option('creer_indice_rewrite_flushed')) {
        flush_rewrite_rules_creer_indice();
    }
}, 20);

/**
 * Détecte l’appel à /creer-indice/ et redirige vers l’indice créé.
 *
 * @return void
 */
function creer_indice_et_rediriger_si_appel(): void
{
    if (get_query_var('creer_indice') !== '1') {
        return;
    }

    $nonce = $_GET['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'creer_indice')) {
        wp_die(__('Action non autorisée.', 'chassesautresor-com'), 'Erreur', ['response' => 403]);
    }

    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }

    $cible_id = isset($_GET['chasse_id']) ? absint($_GET['chasse_id']) : 0;
    $cible_type = 'chasse';
    if (!$cible_id) {
        $cible_id = isset($_GET['enigme_id']) ? absint($_GET['enigme_id']) : 0;
        $cible_type = 'enigme';
    }

    if (!$cible_id) {
        wp_die(__('ID cible manquant.', 'chassesautresor-com'), 'Erreur', ['response' => 400]);
    }

    $indice_id = creer_indice_pour_objet($cible_id, $cible_type);
    if (is_wp_error($indice_id)) {
        $error_message = sanitize_text_field($indice_id->get_error_message());
        $referer       = wp_get_referer() ?: get_permalink($cible_id);
        $redirect_url  = add_query_arg('erreur', $error_message, $referer);
        wp_safe_redirect($redirect_url);
        exit;
    }

    $preview_url = add_query_arg('edition', 'open', get_preview_post_link($indice_id));
    wp_redirect($preview_url);
    exit;
}
add_action('template_redirect', 'creer_indice_et_rediriger_si_appel');

/**
 * AJAX handler returning indices card HTML for a hunt.
 *
 * @return void
 */
function ajax_chasse_lister_indices(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $chasse_id = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;

    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        wp_send_json_error('post_invalide');
    }

    if (!current_user_can('administrator')
        && !utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id)
    ) {
        wp_send_json_error('acces_refuse');
    }

    $indices = get_posts([
        'post_type'      => 'indice',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'pending', 'draft'],
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'   => 'indice_cible',
                'value' => 'chasse',
            ],
            [
                'key'   => 'indice_cible_objet',
                'value' => $chasse_id,
            ],
        ],
    ]);

    ob_start();
    get_template_part(
        'template-parts/chasse/partials/chasse-partial-indices',
        null,
        [
            'chasse_id' => $chasse_id,
            'indices'   => $indices,
        ]
    );
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_chasse_lister_indices', 'ajax_chasse_lister_indices');

/**
 * Pré-remplit automatiquement la chasse liée d'un indice lors de sa création.
 *
 * @param array $field Paramètres du champ ACF.
 * @return array Champ modifié.
 */
function pre_remplir_indice_chasse_linked(array $field): array
{
    global $post;

    if (!$post || get_post_type($post->ID) !== 'indice') {
        return $field;
    }

    $existing = get_post_meta($post->ID, 'indice_chasse_linked', true);
    if (!empty($existing)) {
        return $field;
    }

    $chasse_id   = null;
    $cible_type  = get_field('indice_cible', $post->ID);
    $cible_objet = get_field('indice_cible_objet', $post->ID);

    if ($cible_type === 'chasse') {
        $chasse_id = (int) $cible_objet;
    } elseif ($cible_type === 'enigme') {
        $chasse_id = recuperer_id_chasse_associee((int) $cible_objet);
    } elseif (isset($_GET['chasse_id'])) {
        $chasse_id = (int) $_GET['chasse_id'];
    }

    if ($chasse_id) {
        $field['value'] = $chasse_id;
    }

    return $field;
}
add_filter('acf/load_field/name=indice_chasse_linked', 'pre_remplir_indice_chasse_linked');

/**
 * Sauvegarde la chasse liée si le champ est vide lors de l'enregistrement.
 *
 * @hook acf/save_post
 *
 * @param int|string $post_id ID du post ACF.
 * @return void
 */
function sauvegarder_indice_chasse_si_manquant($post_id): void
{
    if (!is_numeric($post_id) || get_post_type((int) $post_id) !== 'indice') {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $chasse = get_field('indice_chasse_linked', $post_id);
    if ($chasse) {
        return;
    }

    $chasse_id   = null;
    $cible_type  = get_field('indice_cible', $post_id);
    $cible_objet = get_field('indice_cible_objet', $post_id);

    if ($cible_type === 'chasse') {
        $chasse_id = (int) $cible_objet;
    } elseif ($cible_type === 'enigme') {
        $chasse_id = recuperer_id_chasse_associee((int) $cible_objet);
    } elseif (isset($_GET['chasse_id'])) {
        $chasse_id = (int) $_GET['chasse_id'];
    }

    if ($chasse_id) {
        update_field('indice_chasse_linked', $chasse_id, $post_id);
    }
}
add_action('acf/save_post', 'sauvegarder_indice_chasse_si_manquant', 20);
