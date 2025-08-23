<?php
defined('ABSPATH') || exit;

// ==================================================
// 💡 GESTION DES INDICES
// ==================================================
// 🔹 register_endpoint_creer_indice() → Enregistre /creer-indice
// 🔹 creer_indice_pour_objet() → Crée un indice lié à une chasse ou une énigme
// 🔹 creer_indice_et_rediriger_si_appel() → Crée un indice et redirige
// 🔹 rediriger_si_affichage_indice() → Redirige toute page indice vers sa cible
// 🔹 modifier_champ_indice() → Mise à jour AJAX (champ ACF ou natif)

/**
 * Redirige l’affichage d’un indice vers sa chasse ou son énigme liée.
 *
 * @return void
 */
function rediriger_si_affichage_indice(): void
{
    if (!is_singular('indice')) {
        return;
    }

    $indice_id   = get_the_ID();
    $cible_type  = get_field('indice_cible_type', $indice_id);
    $redirect_id = 0;

    if ($cible_type === 'chasse') {
        $redirect_id = (int) get_field('indice_chasse_linked', $indice_id);
    } elseif ($cible_type === 'enigme') {
        $redirect_id = (int) get_field('indice_enigme_linked', $indice_id);
    }

    if ($redirect_id) {
        wp_safe_redirect(get_permalink($redirect_id));
        exit;
    }
}
add_action('template_redirect', 'rediriger_si_affichage_indice');


/**
 * Calcule le rang du prochain indice pour une chasse ou une énigme.
 *
 * @param int    $objet_id   ID de la chasse ou de l’énigme.
 * @param string $objet_type Type de cible ('chasse' ou 'enigme').
 * @return int
 */
function prochain_rang_indice(int $objet_id, string $objet_type): int
{
    $chasse_id = $objet_type === 'chasse'
        ? $objet_id
        : recuperer_id_chasse_associee($objet_id);

    if (!$chasse_id) {
        return 1;
    }

    $existing_indices = function_exists('get_posts')
        ? get_posts([
            'post_type'      => 'indice',
            'post_status'    => ['publish', 'pending', 'draft', 'private', 'future'],
            'meta_query'     => [
                [
                    'key'     => 'indice_chasse_linked',
                    'value'   => '"' . $chasse_id . '"',
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'indice_cache_etat_systeme',
                    'value'   => ['programme', 'accessible'],
                    'compare' => 'IN',
                ],
            ],
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'posts_per_page' => -1,
        ])
        : [];

    return count($existing_indices) + 1;
}

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
    $indice_rank = prochain_rang_indice($objet_id, $objet_type);

    $indice_id = wp_insert_post([
        'post_type'   => 'indice',
        'post_status' => 'pending',
        'post_title'  => TITRE_DEFAUT_INDICE,
        'post_author' => $user_id,
    ]);

    if (is_wp_error($indice_id)) {
        return $indice_id;
    }

    $nouveau_titre = sprintf(__('Indice #%d', 'chassesautresor-com'), $indice_rank);
    wp_update_post([
        'ID'         => $indice_id,
        'post_title' => $nouveau_titre,
    ]);

    update_field('indice_cible_type', $objet_type, $indice_id);
    update_field('indice_chasse_linked', $chasse_id, $indice_id);
    if ($objet_type === 'enigme') {
        update_field('indice_enigme_linked', $objet_id, $indice_id);
    }
    update_field('indice_disponibilite', 'immediate', $indice_id);

    $date_disponibilite = wp_date('Y-m-d H:i:s', (int) current_time('timestamp') + DAY_IN_SECONDS);
    update_field('indice_date_disponibilite', $date_disponibilite, $indice_id);

    update_field('indice_cout_points', 0, $indice_id);
    update_field('indice_cache_complet', false, $indice_id);
    update_field('indice_cache_etat_systeme', 'desactive', $indice_id);

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

    wp_safe_redirect(get_permalink($cible_id));
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

    if (!indice_action_autorisee('edit', 'chasse', $chasse_id)) {
        wp_send_json_error('acces_refuse');
    }

    ob_start();
    get_template_part(
        'template-parts/chasse/partials/chasse-partial-indices',
        null,
        [
            'objet_id'   => $chasse_id,
            'objet_type' => 'chasse',
        ]
    );
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_chasse_lister_indices', 'ajax_chasse_lister_indices');

/**
 * AJAX handler returning indices table HTML.
 *
 * @return void
 */
function ajax_indices_lister_table(): void
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

    if (!indice_action_autorisee('edit', $objet_type, $objet_id)) {
        wp_send_json_error('acces_refuse');
    }

    $per_page = 10;
    $meta     = [
        [
            'key'   => 'indice_cible_type',
            'value' => $objet_type,
        ],
    ];
    if ($objet_type === 'chasse') {
        $meta[] = [
            'key'   => 'indice_chasse_linked',
            'value' => $objet_id,
        ];
    } else {
        $meta[] = [
            'key'   => 'indice_enigme_linked',
            'value' => $objet_id,
        ];
    }

    $query = new WP_Query([
        'post_type'      => 'indice',
        'post_status'    => ['publish', 'pending', 'draft'],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'posts_per_page' => $per_page,
        'paged'          => max(1, $page),
        'meta_query'     => $meta,
    ]);

    ob_start();
    get_template_part('template-parts/common/indices-table', null, [
        'indices'    => $query->posts,
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
add_action('wp_ajax_indices_lister_table', 'ajax_indices_lister_table');

/**
 * Crée un indice via une requête AJAX depuis une modale.
 *
 * @return void
 */
function ajax_creer_indice_modal(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $objet_id   = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
    $objet_type = sanitize_key($_POST['objet_type'] ?? '');

    if (!$objet_id || !in_array($objet_type, ['chasse', 'enigme'], true) || get_post_type($objet_id) !== $objet_type) {
        wp_send_json_error('post_invalide');
    }

    if (!indice_action_autorisee('create', $objet_type, $objet_id)) {
        wp_send_json_error('acces_refuse');
    }

    $indice_id = creer_indice_pour_objet($objet_id, $objet_type);
    if (is_wp_error($indice_id)) {
        wp_send_json_error($indice_id->get_error_message());
    }

    $image   = isset($_POST['indice_image']) ? (int) $_POST['indice_image'] : 0;
    $contenu = wp_kses_post($_POST['indice_contenu'] ?? '');
    $dispo   = sanitize_key($_POST['indice_disponibilite'] ?? 'immediate');
    $date    = sanitize_text_field($_POST['indice_date_disponibilite'] ?? '');

    if ($image) {
        update_field('indice_image', $image, $indice_id);
    }
    if ($contenu !== '') {
        update_field('indice_contenu', $contenu, $indice_id);
    }

    $dispo = $dispo === 'differe' ? 'differe' : 'immediate';
    update_field('indice_disponibilite', $dispo, $indice_id);
    if ($dispo === 'differe' && $date) {
        update_field('indice_date_disponibilite', $date, $indice_id);
    }

    wp_send_json_success(['indice_id' => $indice_id]);
}
add_action('wp_ajax_creer_indice_modal', 'ajax_creer_indice_modal');

/**
 * Met à jour un indice existant via le modal d'édition.
 *
 * @return void
 */
function ajax_modifier_indice_modal(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $indice_id  = isset($_POST['indice_id']) ? (int) $_POST['indice_id'] : 0;
    $objet_id   = isset($_POST['objet_id']) ? (int) $_POST['objet_id'] : 0;
    $objet_type = sanitize_key($_POST['objet_type'] ?? '');

    if (!$indice_id || get_post_type($indice_id) !== 'indice') {
        wp_send_json_error('indice_invalide');
    }
    if (!$objet_id || !in_array($objet_type, ['chasse', 'enigme'], true) || get_post_type($objet_id) !== $objet_type) {
        wp_send_json_error('post_invalide');
    }
    if (!indice_action_autorisee('edit', $objet_type, $objet_id)) {
        wp_send_json_error('acces_refuse');
    }

    $image   = isset($_POST['indice_image']) ? (int) $_POST['indice_image'] : 0;
    $contenu = wp_kses_post($_POST['indice_contenu'] ?? '');
    $dispo   = sanitize_key($_POST['indice_disponibilite'] ?? 'immediate');
    $date    = sanitize_text_field($_POST['indice_date_disponibilite'] ?? '');

    update_field('indice_image', $image, $indice_id);
    update_field('indice_contenu', $contenu, $indice_id);

    $dispo = $dispo === 'differe' ? 'differe' : 'immediate';
    update_field('indice_disponibilite', $dispo, $indice_id);
    if ($dispo === 'differe') {
        update_field('indice_date_disponibilite', $date, $indice_id);
    } else {
        update_field('indice_date_disponibilite', '', $indice_id);
    }

    wp_send_json_success(['indice_id' => $indice_id]);
}
add_action('wp_ajax_modifier_indice_modal', 'ajax_modifier_indice_modal');

/**
 * Gère l’enregistrement AJAX des champs ACF ou natifs du CPT indice.
 *
 * @hook wp_ajax_modifier_champ_indice
 * @return void
 */
function modifier_champ_indice(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $champ   = sanitize_text_field($_POST['champ'] ?? '');
    $valeur  = $_POST['valeur'] ?? '';
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

    if (!$champ || !$post_id || get_post_type($post_id) !== 'indice') {
        wp_send_json_error('⚠️ donnees_invalides');
    }

    if (!utilisateur_peut_modifier_post($post_id) || !utilisateur_peut_editer_champs($post_id)) {
        wp_send_json_error('⚠️ acces_refuse');
    }

    $champ_valide = false;
    $reponse      = ['champ' => $champ, 'valeur' => $valeur];

    if ($champ === 'post_title') {
        $ok = wp_update_post(['ID' => $post_id, 'post_title' => sanitize_text_field($valeur)], true);
        if (is_wp_error($ok)) {
            wp_send_json_error('⚠️ echec_update_post_title');
        }
        wp_send_json_success($reponse);
    }

    switch ($champ) {
        case 'indice_image':
            $champ_valide = update_field('indice_image', (int) $valeur, $post_id) !== false;
            break;
        case 'indice_contenu':
            $champ_valide = update_field('indice_contenu', wp_kses_post($valeur), $post_id) !== false;
            break;
        case 'indice_cible_type':
            $val = $valeur === 'enigme' ? 'enigme' : 'chasse';
            $champ_valide = update_field('indice_cible_type', $val, $post_id) !== false;
            break;
        case 'indice_enigme_linked':
            $ids = array_filter(array_map('intval', explode(',', (string) $valeur)));
            $champ_valide = update_field('indice_enigme_linked', $ids, $post_id) !== false;
            break;
        case 'indice_disponibilite':
            $val = $valeur === 'differe' ? 'differe' : 'immediate';
            $champ_valide = update_field('indice_disponibilite', $val, $post_id) !== false;
            break;
        case 'indice_date_disponibilite':
            $dt = convertir_en_datetime(sanitize_text_field($valeur), ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i']);
            if (!$dt) {
                wp_send_json_error('⚠️ format_date_invalide');
            }
            $champ_valide = update_field('indice_date_disponibilite', $dt->format('Y-m-d H:i:s'), $post_id) !== false;
            break;
        case 'indice_cout_points':
            $champ_valide = update_field('indice_cout_points', (int) $valeur, $post_id) !== false;
            break;
        default:
            wp_send_json_error('⚠️ champ_inconnu');
    }

    if ($champ_valide) {
        if (in_array($champ, ['indice_image', 'indice_contenu', 'indice_disponibilite', 'indice_date_disponibilite'], true)) {
            mettre_a_jour_cache_indice($post_id);
        }

        wp_send_json_success($reponse);
    }

    wp_send_json_error('⚠️ echec_mise_a_jour');
}
add_action('wp_ajax_modifier_champ_indice', 'modifier_champ_indice');

/**
 * Supprime un indice via requête AJAX.
 *
 * @hook wp_ajax_supprimer_indice
 * @return void
 */
function supprimer_indice_ajax(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $indice_id = isset($_POST['indice_id']) ? (int) $_POST['indice_id'] : 0;
    if (!$indice_id || get_post_type($indice_id) !== 'indice') {
        wp_send_json_error('id_invalide');
    }

    $cible_type = get_field('indice_cible_type', $indice_id) === 'enigme' ? 'enigme' : 'chasse';
    if ($cible_type === 'enigme') {
        $linked = get_field('indice_enigme_linked', $indice_id);
        if (is_array($linked)) {
            $first    = $linked[0] ?? null;
            $objet_id = is_array($first) ? (int) ($first['ID'] ?? 0) : (int) $first;
        } else {
            $objet_id = (int) $linked;
        }
    } else {
        $linked = get_field('indice_chasse_linked', $indice_id);
        if (is_array($linked)) {
            $first    = $linked[0] ?? null;
            $objet_id = is_array($first) ? (int) ($first['ID'] ?? 0) : (int) $first;
        } else {
            $objet_id = (int) $linked;
        }
    }

    if (!$objet_id || !indice_action_autorisee('delete', $cible_type, $objet_id)) {
        wp_send_json_error('acces_refuse');
    }

    $deleted = wp_delete_post($indice_id, true);
    if (!$deleted) {
        wp_send_json_error('echec_suppression');
    }

    wp_send_json_success();
}
add_action('wp_ajax_supprimer_indice', 'supprimer_indice_ajax');

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

    $chasse_id = null;
    $cible_type = get_field('indice_cible_type', $post->ID);
    $enigme_id  = get_field('indice_enigme_linked', $post->ID);

    if ($cible_type === 'enigme' && $enigme_id) {
        $chasse_id = recuperer_id_chasse_associee((int) $enigme_id);
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

    $chasse_id  = null;
    $cible_type = get_field('indice_cible_type', $post_id);
    $enigme_id  = get_field('indice_enigme_linked', $post_id);

    if ($cible_type === 'enigme' && $enigme_id) {
        $chasse_id = recuperer_id_chasse_associee((int) $enigme_id);
    } elseif ($cible_type === 'chasse' && isset($_GET['chasse_id'])) {
        $chasse_id = (int) $_GET['chasse_id'];
    } elseif (isset($_GET['chasse_id'])) {
        $chasse_id = (int) $_GET['chasse_id'];
    }

    if ($chasse_id) {
        update_field('indice_chasse_linked', $chasse_id, $post_id);
    }
}
add_action('acf/save_post', 'sauvegarder_indice_chasse_si_manquant', 20);

/**
 * Met à jour les champs de cache d'un indice.
 *
 * @param int|string $post_id ID du post ACF.
 * @return void
 */
function mettre_a_jour_cache_indice($post_id): void
{
    if (!is_numeric($post_id) || get_post_type((int) $post_id) !== 'indice') {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $content  = trim((string) get_field('indice_contenu', $post_id));
    $image_id = get_field('indice_image', $post_id);

    $complete = $content !== '' || !empty($image_id);
    update_field('indice_cache_complet', $complete ? 1 : 0, $post_id);

    $state = 'desactive';
    if ($complete) {
        $availability = get_field('indice_disponibilite', $post_id) ?: 'immediate';
        $state        = 'accessible';

        if ($availability === 'differe') {
            $date_raw = get_field('indice_date_disponibilite', $post_id);
            $date     = $date_raw ? convertir_en_datetime($date_raw) : null;

            if (!$date || $date->getTimestamp() > time()) {
                $state = 'programme';
            }
        }
    }

    update_field('indice_cache_etat_systeme', $state, $post_id);
}

add_action('acf/save_post', 'mettre_a_jour_cache_indice', 30);
