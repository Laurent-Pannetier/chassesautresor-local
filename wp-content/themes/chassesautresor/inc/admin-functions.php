<?php
defined( 'ABSPATH' ) || exit;

const HISTORIQUE_PAIEMENTS_ADMIN_PER_PAGE = 20;
const ORGANISATEURS_PENDING_PER_PAGE     = 20;

// ==================================================
// 📚 SOMMAIRE DU FICHIER
// ==================================================
//
// 1. 📦 FONCTIONNALITÉS ADMINISTRATEUR
// 2. 📦 TAUX DE CONVERSION & PAIEMENT
// 3. 📦 RÉINITIALISATION
// 4. 🛠️ DÉVELOPPEMENT
//


// ==================================================
// 📦 FONCTIONNALITÉS ADMINISTRATEUR
// ==================================================
/**
 * 🔹 rechercher_utilisateur_ajax → Rechercher des utilisateurs en AJAX pour l’autocomplétion.
 * 🔹 traiter_gestion_points → Gérer l’ajout ou le retrait de points à un utilisateur.
 * 🔹 charger_script_autocomplete_utilisateurs → Enregistrer et charger le script de gestion des points dans l’admin (page "Mon Compte").
 * 🔹 gerer_organisateur → Gérer l’acceptation ou le refus d’un organisateur (demande modération).
 */

 
/**
 * 📌 Recherche d'utilisateurs en AJAX pour l'autocomplétion.
 *
 * - Recherche sur `user_login`, `display_name`, et `user_email`.
 * - Aucun filtre par rôle : tous les utilisateurs sont inclus.
 * - Vérification des permissions (`administrator` requis).
 * - Retour JSON des résultats.
 */
function rechercher_utilisateur_ajax() {
    // ✅ Vérifier que la requête est bien envoyée par un administrateur
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => __( '⛔ Accès refusé.', 'chassesautresor-com' )]);
    }

    // ✅ Vérifier la présence du paramètre de recherche
    $search = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

    if (empty($search)) {
        wp_send_json_error(['message' => __( '❌ Requête vide.', 'chassesautresor-com' )]);
    }

    // ✅ Requête pour récupérer tous les utilisateurs sans restriction de rôle
    $users = get_users([
        'search'         => '*' . esc_attr($search) . '*',
        'search_columns' => ['user_login', 'display_name', 'user_email']
    ]);

    // ✅ Vérifier que des utilisateurs sont trouvés
    if (empty($users)) {
        wp_send_json_error(['message' => __( '❌ Aucun utilisateur trouvé.', 'chassesautresor-com' )]);
    }

    // ✅ Formatage des résultats en JSON
    $results = [];
    foreach ($users as $user) {
        $results[] = [
            'id'   => $user->ID,
            'text' => esc_html($user->display_name) . ' (' . esc_html($user->user_login) . ')'
        ];
    }

    wp_send_json_success($results);
}
add_action('wp_ajax_rechercher_utilisateur', 'rechercher_utilisateur_ajax');

/**
 * 📌 Gère l'ajout ou le retrait de points à un utilisateur.
 */
function traiter_gestion_points() {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['modifier_points'])) {
        return;
    }
    
    // ✅ Vérification du nonce pour la sécurité
    if (!isset($_POST['gestion_points_nonce']) || !wp_verify_nonce($_POST['gestion_points_nonce'], 'gestion_points_action')) {
        wp_die( __( '❌ Vérification du nonce échouée.', 'chassesautresor-com' ) );
    }

    // ✅ Vérification que l'utilisateur est administrateur
    if (!current_user_can('administrator')) {
        wp_die( __( '❌ Accès refusé.', 'chassesautresor-com' ) );
    }

    // ✅ Vérification et assainissement des données
    $utilisateur = sanitize_text_field($_POST['utilisateur']);
    $type_modification = sanitize_text_field($_POST['type_modification']);
    $nombre_points = intval($_POST['nombre_points']);

    if (!$utilisateur || !$type_modification || $nombre_points <= 0) {
        wp_die( __( '❌ Données invalides.', 'chassesautresor-com' ) );
    }

    // Récupérer l'ID de l'utilisateur
    $user = get_user_by('ID', intval($utilisateur));
    if (!$user) {
        wp_die( __( '❌ Utilisateur introuvable.', 'chassesautresor-com' ) );
    }

    $user_id = $user->ID;
    $solde_actuel = get_user_points($user_id) ?: 0;

    // Modification des points selon l’action choisie
    if ($type_modification === "ajouter") {
        $delta  = $nombre_points;
        $reason = sprintf('Ajout manuel de %d points', $nombre_points);
    } elseif ($type_modification === "retirer") {
        if ($nombre_points > $solde_actuel) {
            wp_die( __( '❌ Impossible de retirer plus de points que l’utilisateur en possède.', 'chassesautresor-com' ) );
        }
        $delta  = -$nombre_points;
        $reason = sprintf('Retrait manuel de %d points', $nombre_points);
    } else {
        wp_die( __( '❌ Action invalide.', 'chassesautresor-com' ) );
    }

    // Mettre à jour les points de l'utilisateur
    update_user_points($user_id, $delta, $reason, 'admin');

    cat_debug("✅ Points modifiés : $nombre_points $type_modification pour l'utilisateur $utilisateur");

    // ✅ Redirection après soumission
    $redirect_url = add_query_arg(
        [
            'points_modifies' => '1',
        ],
        home_url('/mon-compte/?section=points')
    );

    wp_redirect($redirect_url);
    exit;
}
add_action('init', 'traiter_gestion_points');


/**
 * Enregistre et charge le script de gestion des points pour les administrateurs sur la page "Mon Compte".
 *
 * Cette fonction :
 * - Charge le script `gestion-points.js` uniquement sur la page "Mon Compte".
 * - S'assure que l'utilisateur est un administrateur avant d'ajouter le script.
 * - Utilise `wp_localize_script()` pour rendre l'URL d'AJAX accessible au script JS.
 *
 * @return void
 */
function charger_script_autocomplete_utilisateurs() {
    // Vérifier si l'on est sur la page Mon Compte (y compris ses sous-pages) et que l'utilisateur est administrateur
    if (function_exists('is_account_page') && is_account_page() && current_user_can('administrator')) {
        wp_enqueue_script(
            'autocomplete-utilisateurs', // Nouveau nom du script
            get_stylesheet_directory_uri() . '/assets/js/autocomplete-utilisateurs.js',
            [], // Pas de dépendances spécifiques
            filemtime(get_stylesheet_directory() . '/assets/js/autocomplete-utilisateurs.js'),
            true // Chargement en footer
        );

        // Rendre l'URL AJAX disponible pour le script
        wp_localize_script('autocomplete-utilisateurs', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'charger_script_autocomplete_utilisateurs');

// Fonction principale pour gérer l'acceptation ou le refus
function gerer_organisateur() {
    
    // Vérification des permissions et nonce
    check_ajax_referer('gerer_organisateur_nonce', 'security');
    

    if (!current_user_can('manage_options')) {
        wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'chassesautresor-com' ) ) );
        exit;
    }

    $post_id = intval($_POST['post_id']);
    $type = sanitize_text_field($_POST['type']);

    if (!$post_id || empty($type)) {
        wp_send_json_error( array( 'message' => __( 'Requête invalide.', 'chassesautresor-com' ) ) );
        exit;
    }

    if ($type === "accepter") {
        // Mise à jour du statut de l'organisateur
        wp_update_post(array(
            'ID'          => $post_id,
            'post_status' => 'publish'
        ));

        // Attribution du rôle "Organisateur" à l'auteur de la demande
        $user_id = get_post_field('post_author', $post_id);
        if ($user_id) {
            $user = new WP_User($user_id);
            $user->set_role(ROLE_ORGANISATEUR); // Assurez-vous que ce rôle existe
            // Nettoyer explicitement le rôle organisateur_creation si présent
            $user->remove_role(ROLE_ORGANISATEUR_CREATION);
        }

        // Envoi d'un email de confirmation
        $email = get_post_meta($post_id, 'email_organisateur', true);
        if (!empty($email)) {
            $subject = __('Validation de votre inscription', 'chassesautresor-com');
            $message = '<p>' . esc_html__('Votre demande d\'organisateur a été validée !', 'chassesautresor-com') . '</p>';
            cta_send_email($email, $subject, $message);
        }

        wp_send_json_success(array("message" => "Organisateur accepté."));
    }

    if ($type === "refuser") {
        // Suppression de la demande
        wp_delete_post($post_id, true);

        // Envoi d'un email de refus
        $email = get_post_meta($post_id, 'email_organisateur', true);
        if (!empty($email)) {
            $subject = __('Refus de votre demande', 'chassesautresor-com');
            $message = '<p>' . esc_html__('Votre demande d\'organisateur a été refusée.', 'chassesautresor-com') . '</p>';
            cta_send_email($email, $subject, $message);
        }

        wp_send_json_success(array("message" => "Demande refusée et supprimée."));
    }

    wp_send_json_error( array( 'message' => __( 'Action inconnue.', 'chassesautresor-com' ) ) );
}



// ==================================================
// 📦 TAUX DE CONVERSION & PAIEMENT
// ==================================================
/**
 * 🔹 acf_add_local_field_group (conditionnelle) → Ajouter dynamiquement le champ ACF pour le taux de conversion.
 * 🔹 init_taux_conversion → Initialiser le taux de conversion par défaut s’il n’existe pas.
 * 🔹 get_taux_conversion_actuel → Récupérer le taux de conversion actuel.
 * 🔹 update_taux_conversion → Mettre à jour le taux de conversion et enregistrer l’historique.
 * 🔹 charger_script_taux_conversion → Charger le script `taux-conversion.js` uniquement pour les administrateurs sur "Mon Compte".
 * 🔹 traiter_mise_a_jour_taux_conversion → Mettre à jour le taux de conversion depuis l’administration.
 * 🔹 afficher_tableau_paiements_admin → Afficher les demandes de paiement (en attente ou réglées) pour les administrateurs.
 * 🔹 regler_paiement_admin → Traiter le règlement d’une demande de paiement depuis l’admin.
 * 🔹 traiter_demande_paiement → Traiter la demande de conversion de points en euros pour un organisateur.
 * 🔹 $_SERVER['REQUEST_METHOD'] === 'POST' && isset(...) → Mettre à jour le statut des demandes de paiement (admin).
 */

/**
 * 📌 Valeur minimale de points requise pour demander une conversion.
 */
function get_points_conversion_min(): int {
    return (int) apply_filters('points_conversion_min', 500);
}

/**
 * 📌 Ajout du champ d'administration pour le taux de conversion
 */
add_action('acf/init', function () {
    acf_add_local_field_group([
        'key' => 'group_taux_conversion',
        'title' => 'Paramètres de Conversion',
        'fields' => array(
            array(
                'key' => 'field_taux_conversion',
                'label' => 'Taux de conversion actuel',
                'name' => 'taux_conversion',
                'type' => 'number',
                'instructions' => 'Indiquez le taux de conversion des points en euros (ex : 0.05 pour 1 point = 0.05€).',
                'default_value' => 0.05,
                'step' => 0.001,
                'required' => true,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'options_taux_conversion',
                ),
            ),
        ),
    ]);
});


/**
 * 📌 Initialise le taux de conversion par défaut s'il n'existe pas.
 */
function init_taux_conversion() {
    if (get_option('taux_conversion') === false) {
        update_option('taux_conversion', 85);
    }
}
add_action('init', 'init_taux_conversion');

/**
 * 📌 Récupère le taux de conversion actuel.
 *
 * @return float Le dernier taux enregistré, 85 par défaut.
 */
function get_taux_conversion_actuel() {
    return floatval(get_option('taux_conversion', 85));
}

/**
 * 📌 Met à jour le taux de conversion et enregistre l'historique.
 *
 * @param float $nouveau_taux Nouvelle valeur du taux de conversion.
 */
function update_taux_conversion($nouveau_taux) {
    $historique = get_option('historique_taux_conversion', []);

    // Ajouter la nouvelle entrée dans l'historique
    $historique[] = [
        'date_taux_conversion' => current_time('mysql'),
        'valeur_taux_conversion' => floatval($nouveau_taux)
    ];

    // Limiter l'historique à 10 entrées pour éviter une surcharge inutile
    if (count($historique) > 10) {
        array_shift($historique);
    }

    update_option('taux_conversion', floatval($nouveau_taux));
    update_option('historique_taux_conversion', $historique);
}
/**
 * 📌 Charge le script `taux-conversion.js` uniquement pour les administrateurs sur "Mon Compte" et ses sous-pages (y compris les templates redirigés).
 *
 * - Vérifie si l'URL commence par "/mon-compte/" pour inclure toutes les pages et templates associés.
 * - Vérifie si l'utilisateur a le rôle d'administrateur (`current_user_can('administrator')`).
 * - Si les deux conditions sont remplies, charge le script `taux-conversion.js`.
 */
function charger_script_taux_conversion() {
    if (is_page('mon-compte') && current_user_can('administrator')) {
        wp_enqueue_script(
            'taux-conversion',
            get_stylesheet_directory_uri() . '/assets/js/taux-conversion.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/taux-conversion.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'charger_script_taux_conversion');

/**
 * Load admin payment management script on account pages.
 */
function charger_script_paiements_admin(): void
{
    if (!current_user_can('administrator') || !is_account_page()) {
        return;
    }

    $script_path = get_stylesheet_directory() . '/assets/js/paiements-admin.js';
    wp_enqueue_script(
        'paiements-admin',
        get_stylesheet_directory_uri() . '/assets/js/paiements-admin.js',
        [],
        filemtime($script_path),
        true
    );

    $history_path = get_stylesheet_directory() . '/assets/js/paiements-historique.js';
    wp_enqueue_script(
        'paiements-historique',
        get_stylesheet_directory_uri() . '/assets/js/paiements-historique.js',
        [],
        filemtime($history_path),
        true
    );
}
add_action('wp_enqueue_scripts', 'charger_script_paiements_admin');

/**
 * 📌 Met à jour le taux de conversion depuis l'administration.
 */
function traiter_mise_a_jour_taux_conversion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_taux'])) {
        
        // Vérifier le nonce pour la sécurité
        if (!isset($_POST['modifier_taux_conversion_nonce']) || !wp_verify_nonce($_POST['modifier_taux_conversion_nonce'], 'modifier_taux_conversion_action')) {
            wp_die( __( '❌ Vérification du nonce échouée.', 'chassesautresor-com' ) );
        }

        // Vérifier que l'utilisateur est bien un administrateur
        if (!current_user_can('administrator')) {
            wp_die( __( '❌ Accès refusé.', 'chassesautresor-com' ) );
        }

        // Vérifier et assainir la valeur entrée
        $nouveau_taux = isset($_POST['nouveau_taux']) ? floatval($_POST['nouveau_taux']) : null;
        if ($nouveau_taux === null || $nouveau_taux <= 0) {
            wp_die( __( '❌ Veuillez entrer un taux de conversion valide.', 'chassesautresor-com' ) );
        }

        // Mettre à jour le taux dans les options WordPress
        update_option('taux_conversion', $nouveau_taux);

        // Ajouter l'ancien taux à l'historique
        $historique = get_option('historique_taux_conversion', []);
        $historique[] = [
            'date_taux_conversion' => current_time('mysql'),
            'valeur_taux_conversion' => $nouveau_taux
        ];
        
        // Limiter l'historique à 10 entrées pour éviter une surcharge
        if (count($historique) > 10) {
            array_shift($historique);
        }

        update_option('historique_taux_conversion', $historique);

        // Rediriger avec un message de confirmation
        wp_redirect(add_query_arg('taux_mis_a_jour', '1', wp_get_referer()));
        exit;
    }
}

/**
 * 📌 Affiche les demandes de paiement en attente et réglées pour les administrateurs.
 */
function render_tableau_paiements_admin(array $requests): string
{
    ob_start();
    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>Organisateur</th><th>Montant / Points</th><th>Date demande</th><th>IBAN / BIC</th><th>Statut</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ($requests as $request) {
        $user = get_userdata((int) $request['user_id']);

        $organisateur_id = get_organisateur_from_user($request['user_id']);
        $iban            = $organisateur_id ? get_field('iban', $organisateur_id) : '';
        $bic             = $organisateur_id ? get_field('bic', $organisateur_id) : '';
        if ($organisateur_id && (empty($iban) || empty($bic))) {
            $iban = get_field('gagnez_de_largent_iban', $organisateur_id);
            $bic  = get_field('gagnez_de_largent_bic', $organisateur_id);
        }
        $iban = $iban ?: 'Non renseigné';

        switch ($request['request_status']) {
            case 'paid':
                $statut = '✅ Réglé';
                break;
            case 'cancelled':
                $statut = '❌ Annulé';
                break;
            case 'refused':
                $statut = '🚫 Refusé';
                break;
            default:
                $statut = '🟡 En attente';
        }

        $action = '-';
        if ($request['request_status'] === 'pending') {
            $action  = '<form class="js-update-request" data-id="' . esc_attr($request['id']) . '">';
            $action .= '<select name="statut">';
            $action .= '<option value="regle" selected>' . esc_html__('Régler', 'chassesautresor-com') . '</option>';
            $action .= '<option value="annule">' . esc_html__('Annuler', 'chassesautresor-com') . '</option>';
            $action .= '<option value="refuse">' . esc_html__('Refuser', 'chassesautresor-com') . '</option>';
            $action .= '</select>';
            $action .= '<button type="submit" class="button">OK</button>';
            $action .= '</form>';
        }

        $points_utilises = esc_html(abs((int) $request['points']));

        echo '<tr>';
        echo '<td>' . esc_html($user->display_name ?? '') . '</td>';
        echo '<td>' . esc_html($request['amount_eur']) . ' €<br><small>(' . $points_utilises . ' points)</small></td>';
        echo '<td>' . esc_html(date('Y-m-d H:i', strtotime($request['request_date']))) . '</td>';
        echo '<td><strong>' . esc_html($iban) . '</strong><br><small>' . esc_html($bic) . '</small></td>';
        echo '<td class="col-status">' . esc_html($statut) . '</td>';
        echo '<td>' . $action . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    return ob_get_clean();
}

function afficher_tableau_paiements_admin(): void
{
    if (!current_user_can('administrator')) {
        return;
    }

    global $wpdb;
    $repo     = new PointsRepository($wpdb);
    $requests = $repo->getConversionRequests();

    if (empty($requests)) {
        echo '<p>Aucune demande de paiement en attente.</p>';
        return;
    }

    echo render_tableau_paiements_admin($requests);
}

function recuperer_historique_paiements_admin(int $page = 1): array
{
    global $wpdb;
    $per_page = HISTORIQUE_PAIEMENTS_ADMIN_PER_PAGE;
    $repo     = new PointsRepository($wpdb);
    $offset   = ($page - 1) * $per_page;
    $requests = $repo->getConversionRequests(null, null, $per_page, $offset);

    $table = $wpdb->prefix . 'user_points';
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE origin_type = 'conversion'");
    $pages = max(1, (int) ceil($total / $per_page));

    if (empty($requests)) {
        $html = '<p>Aucune demande de paiement.</p>';
    } else {
        $html  = render_tableau_paiements_admin($requests);
        $html .= '<div class="pager">';
        $html .= '<button class="pager-first" aria-label="Première page"><i class="fa-solid fa-angles-left"></i></button>';
        $html .= '<button class="pager-prev" aria-label="Page précédente"><i class="fa-solid fa-angle-left"></i></button>';
        $html .= '<span class="pager-info">' . $page . ' / ' . $pages . '</span>';
        $html .= '<button class="pager-next" aria-label="Page suivante"><i class="fa-solid fa-angle-right"></i></button>';
        $html .= '<button class="pager-last" aria-label="Dernière page"><i class="fa-solid fa-angles-right"></i></button>';
        $html .= '</div>';
    }

    return [
        'html'  => $html,
        'page'  => $page,
        'pages' => $pages,
    ];
}

function ajax_lister_historique_paiements_admin(): void
{
    if (!current_user_can('administrator')) {
        wp_send_json_error();
    }

    $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
    $data = recuperer_historique_paiements_admin(max(1, $page));
    wp_send_json_success($data);
}
add_action('wp_ajax_lister_historique_paiements', 'ajax_lister_historique_paiements_admin');

/**
 * 💶 Traiter la demande de conversion de points en euros pour un organisateur.
 *
 * Cette fonction s'exécute lors de l'envoi d'un formulaire en POST contenant le champ `demander_paiement`.
 * Elle permet à un utilisateur connecté de :
 * - Vérifier un nonce de sécurité (`demande_paiement_nonce`).
 * - Vérifier qu’il a suffisamment de points pour effectuer la conversion.
 * - Calculer le montant en euros selon le taux de conversion courant.
 * - Déduire les points convertis de son solde.
 * - Envoyer une notification par email à l’administrateur.
 * - Rediriger l’utilisateur vers la page précédente avec un paramètre de confirmation.
 *
 * 💡 Le seuil minimal de conversion est de 500 points.
 * 💡 Le taux de conversion est récupéré via `get_taux_conversion_actuel()`.
 *
 * @return void
 *
 * @hook init
 */
function traiter_demande_paiement() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['demander_paiement'])) {
        return; // 🚫 Ne rien faire si ce n'est pas une requête POST valide
    }

    // ✅ Vérification du nonce pour la sécurité
    if (!isset($_POST['demande_paiement_nonce']) || !wp_verify_nonce($_POST['demande_paiement_nonce'], 'demande_paiement_action')) {
        wp_die( __( '❌ Vérification du nonce échouée.', 'chassesautresor-com' ) );
    }

    // ✅ Vérification de l'utilisateur connecté
    if (!is_user_logged_in()) {
        wp_die( __( '❌ Vous devez être connecté pour effectuer cette action.', 'chassesautresor-com' ) );
    }

    $user_id = get_current_user_id();
    $solde_actuel   = get_user_points($user_id) ?: 0;
    $taux_conversion = get_taux_conversion_actuel();
    $points_minimum  = get_points_conversion_min();

    // ✅ Vérification du nombre de points demandés
    $points_a_convertir = isset($_POST['points_a_convertir']) ? intval($_POST['points_a_convertir']) : 0;

    if ($points_a_convertir < $points_minimum) {
        wp_die(
            sprintf(
                /* translators: %d: points minimum */
                __( '❌ Le minimum pour une conversion est de %d points.', 'chassesautresor-com' ),
                $points_minimum
            )
        );
    }

    if ($points_a_convertir > $solde_actuel) {
        wp_die( __( '❌ Vous n\'avez pas assez de points pour effectuer cette conversion.', 'chassesautresor-com' ) );
    }

    // ✅ Calcul du montant en euros
    $montant_euros = round(($points_a_convertir / 1000) * $taux_conversion, 2);

    global $wpdb;
    $repo   = new PointsRepository($wpdb);
    $log_id = $repo->logConversionRequest($user_id, -$points_a_convertir, $montant_euros);
    cat_debug("✅ Demande enregistrée : log_id {$log_id}");

    // 📧 Notification admin
    $admin_email = get_option('admin_email');
    $subject = __('Nouvelle demande de paiement', 'chassesautresor-com');
    $message  = '<p>' . esc_html__('Une nouvelle demande de paiement a été soumise.', 'chassesautresor-com') . '</p>';
    $message .= '<p>';
    $message .= esc_html__('Organisateur ID :', 'chassesautresor-com') . ' ' . intval($user_id) . '<br />';
    $message .= esc_html__('Montant :', 'chassesautresor-com') . ' ' . esc_html(number_format($montant_euros, 2, ',', ' ')) . ' €<br />';
    $message .= esc_html__('Points utilisés :', 'chassesautresor-com') . ' ' . intval($points_a_convertir) . ' ' . esc_html__('points', 'chassesautresor-com') . '<br />';
    $message .= esc_html__('Date :', 'chassesautresor-com') . ' ' . esc_html(current_time('mysql')) . '<br />';
    $message .= esc_html__('Statut : En attente', 'chassesautresor-com');
    $message .= '</p>';

    cta_send_email($admin_email, $subject, $message);
    cat_debug("📧 Notification envoyée à l'administrateur.");

    // ✅ Redirection après soumission
    wp_safe_redirect(home_url('/mon-compte/?section=points'));
    exit;
}
add_action('init', 'traiter_demande_paiement');

// ----------------------------------------------------------
// 🎛️ Mise à jour du statut des demandes de paiement (Admin)
// ----------------------------------------------------------
/**
 * Update monthly total of organizer payouts.
 */
function mettre_a_jour_paiements_organisateurs(float $amount): void
{
    $option = 'total_paiements_effectues_mensuel_' . date('Y_m');

    if (function_exists('get_option') && function_exists('update_option')) {
        $current = (float) get_option($option, 0);
        update_option($option, $current + $amount);
    }
}

/**
 * Handle AJAX status updates for payment requests.
 */
function ajax_update_request_status(): void
{
    if (!current_user_can('administrator')) {
        wp_send_json_error();
    }

    $paiement_id = isset($_POST['paiement_id']) ? intval($_POST['paiement_id']) : 0;
    $statut      = isset($_POST['statut']) ? sanitize_text_field($_POST['statut']) : '';

    if (!$paiement_id || !in_array($statut, ['regle', 'annule', 'refuse'], true)) {
        wp_send_json_error();
    }

    global $wpdb;
    $repo = new PointsRepository($wpdb);

    $map = [
        'regle'  => 'paid',
        'annule' => 'cancelled',
        'refuse' => 'refused',
    ];

    $repoStatus = $map[$statut];
    $dates      = [];
    if ($repoStatus === 'paid') {
        $dates['settlement_date'] = current_time('mysql');
    } else {
        $dates['cancelled_date'] = current_time('mysql');
    }

    $repo->updateRequestStatus($paiement_id, $repoStatus, $dates);
    cat_debug("✅ Statut mis à jour pour l'entrée {$paiement_id} : {$repoStatus}");

    if (in_array($repoStatus, ['cancelled', 'refused'], true)) {
        $request = $repo->getRequestById($paiement_id);
        if ($request) {
            $points = abs((int) $request['points']);
            $reason = sprintf(
                'Restauration de %d points après annulation/refus',
                $points
            );
            update_user_points(
                (int) $request['user_id'],
                $points,
                $reason,
                'admin',
                $paiement_id
            );
        }
    } elseif ($repoStatus === 'paid') {
        $request = $repo->getRequestById($paiement_id);
        if ($request) {
            $montant_paye = floatval($request['amount_eur']);
            mettre_a_jour_paiements_organisateurs($montant_paye);
        }
    }

    wp_send_json_success(['status' => $repoStatus]);
}
add_action('wp_ajax_update_conversion_status', 'ajax_update_request_status');



// ==================================================
// 📦 RÉINITIALISATION
// ==================================================
/**
 * 🔹 traiter_reinitialisation_stats → Réinitialiser les statistiques globales du site.
 * 🔹 ajouter_bouton_reinitialisation_stats → Ajouter une option pour activer ou désactiver la réinitialisation.
 * 🔹 gerer_activation_reinitialisation_stats → Gérer l’activation ou la désactivation de la réinitialisation des stats.
 * 🔹 supprimer_metas_organisateur → Supprimer les métadonnées liées aux organisateurs.
 * 🔹 supprimer_metas_utilisateur → Supprimer les métadonnées des utilisateurs (optimisé).
 * 🔹 supprimer_metas_globales → Supprimer les métadonnées globales stockées dans `option_meta`.
 * 🔹 supprimer_metas_post → Supprimer les métadonnées des énigmes et chasses (optimisé).
 * 🔹 supprimer_souscriptions_utilisateur → Supprimer les souscriptions des joueurs aux énigmes.
 * 🔹 reinitialiser_enigme → Réinitialiser l’état d’une énigme pour un utilisateur donné.
 * 🔹 bouton_reinitialiser_enigme_callback → Afficher le bouton de réinitialisation si l’utilisateur a résolu l’énigme.
 */


/**
 * 🔄 Réinitialiser les statistiques globales du site (administrateur uniquement).
 *
 * Cette fonction est déclenchée par le hook `admin_post_reset_stats_action`
 * lorsqu’un formulaire POST est soumis avec le champ `reset_stats`
 * et le nonce `reset_stats_nonce`. Elle permet de :
 *
 * 1. 🧹 Supprimer toutes les métadonnées utilisateurs liées aux statistiques :
 *    - total de chasses terminées, énigmes jouées, points dépensés/gagnés, etc.
 *
 * 2. 🧹 Supprimer toutes les métadonnées des posts (CPT `enigme` et `chasse`) liées
 *    aux tentatives, indices, progression et joueurs associés.
 *
 * 3. 🗑 Supprimer le taux de conversion (ACF) enregistré dans le post `Paiements`.
 *
 * 4. 🧹 Supprimer les métadonnées globales du site et des organisateurs (via fonctions dédiées).
 *
 * 5. 🔧 Supprimer l’option `activer_reinitialisation_stats` pour éviter un double déclenchement.
 *
 * 6. 🚀 Rediriger vers la page d’administration dédiée une fois la suppression terminée.
 *
 * 🔐 La fonction ne s’exécute que :
 * - en contexte admin,
 * - si l’utilisateur est administrateur,
 * - si le nonce est valide,
 * - et si l’option `activer_reinitialisation_stats` est activée.
 *
 * @return void
 *
 * @hook admin_post_reset_stats_action
 */
function traiter_reinitialisation_stats() {
    if (!is_admin() || !current_user_can('administrator')) return;
    if (!isset($_POST['reset_stats']) || !check_admin_referer('reset_stats_action', 'reset_stats_nonce')) return;
    if (!get_option('activer_reinitialisation_stats', false)) return; // Vérification activée

    cat_debug("🛠 Début de la suppression des statistiques...");

    supprimer_metas_utilisateur([
        'total_enigmes_jouees', 'total_chasses_terminees', 'total_indices_debloques',
        'total_points_depenses', 'total_points_gagnes', 'total_enigmes_trouvees'
    ]);
    supprimer_souscriptions_utilisateur();

    supprimer_metas_post('enigme', [
        'total_tentatives_enigme', 'total_indices_debloques_enigme',
        'total_points_depenses_enigme', 'total_joueurs_ayant_resolu_enigme',
        'total_joueurs_souscription_enigme', 'progression_joueurs'
    ]);

    supprimer_metas_post('chasse', [
        'total_tentatives_chasse', 'total_indices_debloques_chasse',
        'total_points_depenses_chasse', 'total_joueurs_souscription_chasse',
        'progression_joueurs'
    ]);

    // 🚀 SUPPRESSION DES TAUX DE CONVERSION ACF
    $paiements_post = get_posts([
        'post_type'      => 'administration',
        'posts_per_page' => 1,
        'title'          => 'Paiements',
        'post_status'    => 'private'
    ]);

    if (!empty($paiements_post)) {
        $post_id = $paiements_post[0]->ID;
        delete_field('taux_conversion', $post_id);
        cat_debug("✅ Taux de conversion réinitialisé pour le post ID : {$post_id}");
    } else {
        cat_debug("⚠️ Aucun post 'Paiements' trouvé, impossible de réinitialiser les taux.");
    }
    supprimer_metas_globales();
    supprimer_metas_organisateur();


    // 🔄 Désactiver l'option après suppression
    delete_option('activer_reinitialisation_stats');

    cat_debug("✅ Statistiques réinitialisées avec succès.");

    // ✅ Vérification du problème d'écran blanc
    cat_debug("✅ Fin du script, lancement de la redirection...");
    
    // Vérifier si les headers sont déjà envoyés
    if (!headers_sent()) {
        wp_redirect(home_url('/administration/outils/?updated=true'));
        exit;
    } else {
        cat_debug("⛔ Problème de redirection : headers déjà envoyés.");
        die("⛔ Problème de redirection. Recharge manuelle nécessaire.");
    }
}
add_action('admin_post_reset_stats_action', 'traiter_reinitialisation_stats');


/**
 * ⚙️ Affiche l'interface d'administration pour activer et déclencher la réinitialisation des statistiques.
 *
 * Cette fonction génère un bloc HTML dans une page d'administration personnalisée,
 * visible uniquement pour les administrateurs.
 *
 * Elle propose deux actions :
 *
 * 1. ✅ Un **checkbox** pour activer ou désactiver la réinitialisation des stats, 
 *    enregistrée dans l'option `activer_reinitialisation_stats`.
 *
 * 2. ⚠️ Un **bouton de réinitialisation** (affiché uniquement si activé), qui soumet une requête POST
 *    vers `admin_post_reset_stats_action` (gérée par la fonction `traiter_reinitialisation_stats()`).
 *
 * 🔐 La fonction est protégée :
 * - par une vérification de rôle (`administrator`)
 * - par un nonce de sécurité (`reset_stats_action`)
 *
 * 📝 L'action est irréversible : elle supprime toutes les métadonnées statistiques
 * liées aux utilisateurs, énigmes, chasses, et réglages globaux.
 *
 * @return void
 */
function ajouter_bouton_reinitialisation_stats() {
    if (!current_user_can('administrator')) return;

    $reinit_active = get_option('activer_reinitialisation_stats', false);

    ?>
    <div class="wrap">
        <h2>Réinitialisation des Statistiques</h2>
        <p>⚠️ <strong>Attention :</strong> Cette action est irréversible. Toutes les statistiques des joueurs, énigmes et chasses seront supprimées.</p>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('toggle_reinit_stats_action', 'toggle_reinit_stats_nonce'); ?>
            <input type="hidden" name="action" value="toggle_reinit_stats_action">

            <label>
                <input type="checkbox" name="activer_reinit" value="1" <?php checked($reinit_active, true); ?>>
                Activer la réinitialisation des statistiques
            </label>

            <br><br>
            <input type="submit" name="enregistrer_reinit" class="button button-primary" value="Enregistrer">
        </form>

        <?php if ($reinit_active): ?>
            <br>
            <form method="post">
                <?php wp_nonce_field('reset_stats_action', 'reset_stats_nonce'); ?>
                <input type="submit" name="reset_stats" class="button button-danger" value="⚠️ Réinitialiser toutes les statistiques" 
                       onclick="return confirm('⚠️ ATTENTION : Cette action est irréversible. Confirmez-vous la réinitialisation ?');">
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * 📌 Gestion de l'activation/désactivation de la réinitialisation des stats
 */
function gerer_activation_reinitialisation_stats() {
    cat_debug("🛠 Début du traitement de l'activation/désactivation");

    // ✅ Vérification des permissions administrateur
    if (!current_user_can('manage_options')) {
        cat_debug("⛔ Problème de permission : utilisateur non autorisé.");
        wp_die( __( '⛔ Accès refusé. Vous n’avez pas la permission d’effectuer cette action.', 'chassesautresor-com' ) );
    }
    cat_debug("🔎 Permission OK");

    // ✅ Vérification de la requête POST et de la sécurité
    if (!isset($_POST['enregistrer_reinit']) || !check_admin_referer('toggle_reinit_stats_action', 'toggle_reinit_stats_nonce')) {
        cat_debug("⛔ Problème de nonce ou bouton non soumis.");
        wp_die( __( '⛔ Erreur de sécurité. Veuillez réessayer.', 'chassesautresor-com' ) );
    }
    cat_debug("🔎 Nonce OK");

    // ✅ Mise à jour de l'option d'activation
    $activer = isset($_POST['activer_reinit']) ? 1 : 0;
    update_option('activer_reinitialisation_stats', $activer);
    cat_debug("✅ Option mise à jour : " . ($activer ? 'Activée' : 'Désactivée'));

    // ✅ Ajout d’un message d’alerte WordPress
    add_action('admin_notices', function() use ($activer) {
        echo '<div class="updated"><p>✅ Réinitialisation des stats ' . ($activer ? 'activée' : 'désactivée') . '.</p></div>';
    });

    // ✅ Vérification de la redirection
    $page_outils = get_page_by_path('administration/outils');
    if ($page_outils) {
        $redirect_url = get_permalink($page_outils) . '?updated=true';
    } else {
        $redirect_url = home_url('/administration/outils/?updated=true');
    }

    cat_debug("🔄 Redirection vers : " . $redirect_url);
    if (!headers_sent()) {
        wp_redirect($redirect_url);
        exit;
    } else {
        cat_debug("⛔ Problème de redirection : headers déjà envoyés.");
    }

    exit;
}
add_action('admin_post_toggle_reinit_stats_action', 'gerer_activation_reinitialisation_stats');

/**
 * 📌 Supprime les méta liées aux organisateurs
 * - Points perçus par les organisateurs
 * - Historique des paiements aux organisateurs
 */
function supprimer_metas_organisateur() {
    global $wpdb;

    $meta_keys = [
        'total_points_percus_organisateur',
        'demande_paiement' // Suppression de l'historique des paiements
    ];

    // Récupération des utilisateurs ayant un rôle d'organisateur
    $organisateurs = get_users([
        'role' => ROLE_ORGANISATEUR,
        'fields' => 'ID'
    ]);

    if (empty($organisateurs)) {
        cat_debug("ℹ️ Aucun organisateur trouvé. Rien à supprimer.");
        return;
    }

    foreach ($organisateurs as $user_id) {
        foreach ($meta_keys as $meta_key) {
            // Vérifie si la méta existe avant suppression
            $meta_existante = get_user_meta($user_id, $meta_key, true);
            if (!empty($meta_existante)) {
                if ($meta_key === 'demande_paiement') {
                    // Suppression forcée via SQL pour l'historique des paiements
                    $wpdb->delete($wpdb->usermeta, ['user_id' => $user_id, 'meta_key' => $meta_key]);
                    cat_debug("✅ Suppression forcée via SQL pour : {$meta_key} (user_id {$user_id})");
                } else {
                    // Suppression normale pour les autres méta
                    delete_user_meta($user_id, $meta_key);
                    cat_debug("✅ Suppression réussie de : {$meta_key} pour user_id {$user_id}");
                }

                // Vérification post-suppression
                $meta_post_suppression = get_user_meta($user_id, $meta_key, true);
                if (!empty($meta_post_suppression)) {
                    cat_debug("⚠️ Problème : {$meta_key} n'a pas été supprimé pour user_id {$user_id}.");
                } else {
                    cat_debug("✅ Vérification OK : {$meta_key} a bien été supprimé pour user_id {$user_id}.");
                }
            } else {
                cat_debug("ℹ️ Aucune méta trouvée pour : {$meta_key} de user_id {$user_id}.");
            }
        }
    }
}


/**
 * 📌 Suppression optimisée des métas utilisateurs
 */
function supprimer_metas_utilisateur($meta_keys) {
    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));

    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ($placeholders)",
        ...$meta_keys
    ));

    // Vérification d'erreur SQL
    if (!empty($wpdb->last_error)) {
        cat_debug("⚠️ Erreur SQL lors de la suppression des metas utilisateur : " . $wpdb->last_error);
    }
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'enigme_%_resolue'");

}

/**
 * 📌 Supprime les méta globales stockées en `option_meta`
 */
function supprimer_metas_globales() {
    $metas_globales = [
        'total_points_depenses_mois_' . date('Y_m'),
        'total_points_vendus_mensuel_' . date('Y_m'),
        'revenu_total_site',
        'revenu_total_site_mensuel_' . date('Y_m'),
        'total_paiements_effectues_mensuel_' . date('Y_m'),
        'total_points_en_circulation'
    ];

    foreach ($metas_globales as $meta) {
        delete_option($meta);
        cat_debug("✅ Suppression réussie de l'option : $meta");
    }
}

/**
 * 📌 Suppression optimisée des métas des énigmes et chasses
 */
function supprimer_metas_post($post_type, $meta_keys) {
    global $wpdb;

    $post_ids = get_posts([
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'fields'         => 'ids'
    ]);

    if (empty($post_ids)) {
        cat_debug("ℹ️ Aucun post trouvé pour le type : {$post_type}. Rien à supprimer.");
        return;
    }

    foreach ($meta_keys as $meta_key) {
        // 🔍 Vérifier si la méta existe avant suppression
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            $meta_key . '%'
        ));

        if ($existe > 0) {
            // 🚀 Suppression optimisée de toutes les variations de la méta
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
                $meta_key . '%'
            ));
            cat_debug("✅ Suppression réussie pour : {$meta_key}%");
        } else {
            cat_debug("ℹ️ Aucune méta trouvée pour : {$meta_key}%");
        }
    }
}

/**
 * 📌 Suppression des souscriptions des joueurs aux énigmes
 */
function supprimer_souscriptions_utilisateur() {
    global $wpdb;

    // 🚀 Suppression de toutes les souscriptions utilisateur pour toutes les énigmes
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'enigme_%_souscrit'");

    if (!empty($wpdb->last_error)) {
        cat_debug("⚠️ Erreur SQL lors de la suppression des souscriptions utilisateur : " . $wpdb->last_error);
    } else {
        cat_debug("✅ Suppression réussie des souscriptions aux énigmes.");
    }
}

/**
 * 🔄 Réinitialise l’état d’une énigme pour un utilisateur donné :
 * - Supprime le statut et la date de résolution.
 * - Réinitialise les indices débloqués.
 * - Réinitialise le statut de la chasse si nécessaire.
 * - Nettoie les caches liés à l’utilisateur et à l’énigme.
 *
 * @param int $user_id ID de l’utilisateur.
 * @param int $enigme_id ID de l’énigme.
 */
function reinitialiser_enigme($user_id, $enigme_id) {
    if (!is_numeric($user_id) || !is_numeric($enigme_id)) {
        cat_debug("⚠️ Paramètres invalides : user_id={$user_id}, enigme_id={$enigme_id}");
        return;
    }

    cat_debug("🔄 DÉBUT de la réinitialisation pour l'utilisateur (ID: {$user_id}) sur l'énigme (ID: {$enigme_id})");

    // 🧹 1. Suppression du statut et de la date de résolution
    delete_user_meta($user_id, "statut_enigme_{$enigme_id}");
    delete_user_meta($user_id, "enigme_{$enigme_id}_resolution_date");
    cat_debug("🧹 Statut et date de résolution supprimés pour l'énigme (ID: {$enigme_id})");

    // 🗑️ 2. Réinitialisation des indices débloqués
    $indices = get_field('indices', $enigme_id); 
    if (!empty($indices) && is_array($indices)) {
        foreach ($indices as $index => $indice) {
            delete_user_meta($user_id, "indice_debloque_{$enigme_id}_{$index}");
        }
        cat_debug("🧹 Indices débloqués réinitialisés pour l'énigme (ID: {$enigme_id})");
    }

    // 🏴‍☠️ 3. Gestion de la chasse associée
    $chasse_id = get_field('chasse_associee', $enigme_id, false);
    $chasse_id = is_array($chasse_id) ? reset($chasse_id) : $chasse_id;

    if ($chasse_id && is_numeric($chasse_id)) {
        // 🔄 Si la chasse est en mode "stop" et terminée, la remettre en cours
        $illimitee = get_field('illimitee', $chasse_id); // Récupère le mode de la chasse (stop / continue)
        $statut_chasse = get_field('statut_chasse', $chasse_id);
        
        // Vérifie si la chasse est en mode "stop" et si elle est terminée
        if ($illimitee === 'stop' && in_array(mb_strtolower($statut_chasse), ['termine', 'terminée', 'terminé'], true)) {
            update_field('statut_chasse', 'en cours', $chasse_id);
            update_field('gagnant', '', $chasse_id);
            update_field('date_de_decouverte', null, $chasse_id);
        
            delete_post_meta($chasse_id, 'statut_chasse');
            delete_post_meta($chasse_id, 'gagnant');
            delete_post_meta($chasse_id, 'date_de_decouverte');
        
            wp_cache_delete($chasse_id, 'post_meta');
            clean_post_cache($chasse_id);
        
            cat_debug("🔄 Chasse (ID: {$chasse_id}) réinitialisée : statut 'en cours', gagnant et date supprimés.");
        }
    }

    // 🚀 5. (Optionnel) Réinitialisation de la souscription pour permettre de rejouer immédiatement
    // Décommentez la ligne suivante si vous souhaitez que le bouton "JOUER" apparaisse directement après réinitialisation :
    // update_user_meta($user_id, "statut_enigme_{$enigme_id}", 'souscrit');
    // cat_debug("🔄 Souscription réinitialisée pour l'énigme (ID: {$enigme_id}) → bouton 'JOUER' réactivé.");

    // 🧹 6. Nettoyage des caches
    // 🚀 5. Rafraîchissement des caches WordPress pour garantir l'affichage correct
wp_cache_delete($user_id, 'user_meta'); // Supprime le cache des métas utilisateur
wp_cache_delete("statut_enigme_{$enigme_id}", 'user_meta'); // Supprime le cache spécifique du statut d'énigme
wp_cache_delete($enigme_id, 'post_meta'); // Supprime le cache des métas du post (énigme)

clean_user_cache($user_id); // Nettoie le cache complet de l'utilisateur
clean_post_cache($enigme_id); // Nettoie le cache du post énigme

cat_debug("🔄 Caches utilisateur et post nettoyés après réinitialisation.");
cat_debug("✅ Réinitialisation complète terminée pour l'utilisateur (ID: {$user_id}) sur l'énigme (ID: {$enigme_id})");

}

/**
 * 🔄 Affiche le bouton de réinitialisation si l'utilisateur a résolu l'énigme.
 *
 * Conditions :
 * - Affiche si le statut de l’énigme est "resolue" ou "terminee_resolue".
 *
 * @return string HTML du bouton ou chaîne vide si non applicable.
 */
function bouton_reinitialiser_enigme_callback() {
    if (!is_user_logged_in() || !is_singular('enigme') || !current_user_can('administrator')) return ''; // 🚫 Restreint aux admins

    $user_id = get_current_user_id();
    $enigme_id = get_the_ID();
    $statut = enigme_get_statut($enigme_id, $user_id); // 🔄 Utilisation du statut centralisé

    // ✅ Affiche le bouton uniquement si l'énigme est resolue ou terminee_resolue
    if (!in_array($statut, ['resolue', 'terminee_resolue'])) return '';

    return "
        <form method='post' class='form-reinitialiser-enigme'>
            <button type='submit' name='reinitialiser_enigme' class='bouton-action bouton-reinitialiser dynamique-{$statut}'>
                🔄 Réinitialiser l’énigme
            </button>
        </form>";
}



// ==================================================
// 🛠️ DÉVELOPPEMENT
// ==================================================
/**
 * 🔹 acf_inspect_field_group → Affiche les détails d’un groupe de champs ACF dans le navigateur pour documentation manuelle.
 */


/**
 * Affiche de manière lisible les détails d’un groupe de champs ACF dans le navigateur.
 *
 * @param int|string $group_id_or_key L'ID ou la key du groupe de champs ACF.
 */
function acf_inspect_field_group($group_id_or_key) {
    if (!function_exists('acf_get_fields')) {
        echo '<pre>ACF non disponible.</pre>';
        return;
    }

    $group = null;
    $group_key = '';

    // Cas : ID numérique
    if (is_numeric($group_id_or_key)) {
        $group = get_post((int)$group_id_or_key);
        if (!$group || $group->post_type !== 'acf-field-group') {
            echo "<pre>❌ Aucun groupe ACF trouvé pour l’ID {$group_id_or_key}.</pre>";
            return;
        }
        $group_key = get_post_meta($group->ID, '_acf_field_group_key', true);
        if (empty($group_key)) {
            echo "<pre>❌ La clé du groupe ACF est introuvable pour l’ID {$group->ID}.</pre>";
            return;
        }
    }

    // Cas : clé fournie directement
    if (!is_numeric($group_id_or_key)) {
        $group_key = $group_id_or_key;
        $group = acf_get_field_group($group_key);
        if (!$group) {
            echo "<pre>❌ Aucun groupe ACF trouvé pour la key {$group_key}.</pre>";
            return;
        }
    }

    // Récupération des champs
    $fields = acf_get_fields($group_key);
    if (empty($fields)) {
        echo "<pre>⚠️ Aucun champ trouvé pour le groupe « {$group->title} » (Key : {$group_key})</pre>";
        return;
    }

    // Affichage
    echo '<pre>';
    $title = is_array($group) ? $group['title'] : $group->post_title;
    $id    = is_array($group) ? $group['ID']    : $group->ID;
    
    echo "🔹 Groupe : {$title}\n";
    echo "🆔 ID : {$id}\n";

    echo "🔑 Key : {$group_key}\n";
    echo "📦 Champs trouvés : " . count($fields) . "\n\n";
    afficher_champs_acf_recursifs($fields);
    echo '</pre>';
}


/**
 * Fonction récursive pour afficher les champs ACF avec indentation.
 *
 * @param array $fields Tableau de champs ACF.
 * @param int $indent Niveau d'indentation.
 */
function afficher_champs_acf_recursifs($fields, $indent = 0) {
    $prefix = str_repeat('  ', $indent);
    foreach ($fields as $field) {
        echo $prefix . "— " . $field['name'] . " —\n";
        echo $prefix . "Type : " . $field['type'] . "\n";
        echo $prefix . "Label : " . $field['label'] . "\n";
        echo $prefix . "Instructions : " . (!empty($field['instructions']) ? $field['instructions'] : '(vide)') . "\n";
        echo $prefix . "Requis : " . ($field['required'] ? 'oui' : 'non') . "\n";

        // Options spécifiques selon le type
        if (!empty($field['choices'])) {
            echo $prefix . "Choices :\n";
            foreach ($field['choices'] as $key => $label) {
                echo $prefix . "  - {$key} : {$label}\n";
            }
        }

        if (in_array($field['type'], ['repeater', 'group', 'flexible_content']) && !empty($field['sub_fields'])) {
            echo $prefix . "Contenu imbriqué :\n";
            afficher_champs_acf_recursifs($field['sub_fields'], $indent + 1);
        }

        echo $prefix . str_repeat('-', 40) . "\n";
    }
}
/*
| 💡 Ce bloc est désactivé par défaut. Il sert uniquement à afficher
| temporairement le détail d’un groupe de champs ACF dans l’interface admin.
|
| 📋 Pour l’utiliser :
|   1. Décommente les lignes ci-dessous
|   2. Remplace l’ID (ex. : 9) par celui du groupe souhaité
|   3. Recharge une page de l’admin (ex : Tableau de bord)
|   4. Copie le résultat affiché et re-commente le bloc après usage
|
| ❌ À ne jamais laisser actif en production.
*/

/*

📋 Liste des groupes ACF disponibles :
========================================

🆔 ID     : 27
🔑 Key    : group_67b58c51b9a49
🏷️  Titre : paramètre de la chasse
----------------------------------------
🆔 ID     : 9
🔑 Key    : group_67b58134d7647
🏷️  Titre : Paramètres de l’énigme
----------------------------------------

🆔 ID     : 657
🔑 Key    : group_67c7dbfea4a39
🏷️  Titre : Paramètres organisateur
----------------------------------------
🆔 ID     : 584
🔑 Key    : group_67c28f6aac4fe
🏷️  Titre : Statistiques des chasses
----------------------------------------
🆔 ID     : 577
🔑 Key    : group_67c2368625fc2
🏷️  Titre : Statistiques des énigmes
----------------------------------------
🆔 ID     : 931
🔑 Key    : group_67cd4a8058510
🏷️  Titre : infos éditions chasse
----------------------------------------

add_action('admin_notices', function() {
    if (current_user_can('administrator')) {
        acf_inspect_field_group('group_67c28f6aac4fe'); // Remplacer  Key
    }
});

*/

// =============================================
// AJAX : récupérer les détails des groupes ACF
// =============================================
function recuperer_details_acf() {
    if (!current_user_can('administrator')) {
        wp_send_json_error( __( 'Non autorisé', 'chassesautresor-com' ) );
    }

    // Utilisation des "keys" ACF directement car les IDs ne sont pas fiables
    // lorsque les groupes sont chargés via JSON local.
    $group_keys = [
        'group_67b58c51b9a49', // Paramètre de la chasse (ID 27)
        'group_67b58134d7647', // Paramètres de l’énigme (ID 9)
        'group_67c7dbfea4a39', // Paramètres organisateur (ID 657)
        'group_68a1fb240748a', // Paramètres indices
        'group_68abd01f80aee', // Paramètres solution
    ];

    ob_start();
    foreach ($group_keys as $key) {
        acf_inspect_field_group($key);
        echo "\n";
    }
    $output = ob_get_clean();
    $output = wp_strip_all_tags($output);
    wp_send_json_success($output);
}
add_action('wp_ajax_recuperer_details_acf', 'recuperer_details_acf');

function cta_reset_stats() {
    if (!current_user_can('administrator')) {
        wp_send_json_error(__('Non autorisé', 'chassesautresor-com'));
    }

    check_ajax_referer('cta_reset_stats', 'nonce');

    global $wpdb;
    $tables = [
        $wpdb->prefix . 'chasse_winners',
        $wpdb->prefix . 'engagements',
        $wpdb->prefix . 'enigme_statuts_utilisateur',
        $wpdb->prefix . 'enigme_tentatives',
        $wpdb->prefix . 'user_points',
        $wpdb->prefix . 'indices_deblocages',
    ];

    $total_deleted = 0;

    foreach ($tables as $table) {
        $wpdb->query("DELETE FROM {$table}");

        if (!empty($wpdb->last_error)) {
            wp_send_json_error($wpdb->last_error);
        }

        $total_deleted += (int) $wpdb->rows_affected;
    }

    delete_metadata('user', 0, '_myaccount_messages', '', true);

    if (!empty($wpdb->last_error)) {
        wp_send_json_error($wpdb->last_error);
    }

    $total_deleted += (int) $wpdb->rows_affected;

    $user_ids = $wpdb->get_col(
        "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE 'statut_enigme_%' OR meta_key LIKE 'enigme_%_resolution_date' OR meta_key LIKE 'indice_debloque_%' OR meta_key LIKE 'souscription_chasse_%'"
    );

    $patterns = [
        'statut_enigme_%',
        'enigme_%_resolution_date',
        'indice_debloque_%',
        'souscription_chasse_%',
    ];

    foreach ($patterns as $pattern) {
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '{$pattern}'");

        if (!empty($wpdb->last_error)) {
            wp_send_json_error($wpdb->last_error);
        }

        $total_deleted += (int) $wpdb->rows_affected;
    }

    foreach ($user_ids as $user_id) {
        clean_user_cache((int) $user_id);
    }

    $chasses_terminees = get_posts([
        'post_type'   => 'chasse',
        'post_status' => 'any',
        'meta_query'  => [
            [
                'key'   => 'chasse_cache_statut',
                'value' => 'termine',
            ],
        ],
        'fields'   => 'ids',
        'nopaging' => true,
    ]);

    foreach ($chasses_terminees as $chasse_id) {
        update_field('chasse_cache_statut', 'en_cours', $chasse_id);
        delete_field('chasse_cache_gagnants', $chasse_id);
        delete_field('chasse_cache_date_decouverte', $chasse_id);
    }

    $all_chasses = get_posts([
        'post_type'   => 'chasse',
        'post_status' => 'any',
        'fields'      => 'ids',
        'nopaging'    => true,
    ]);

    foreach ($all_chasses as $chasse_id) {
        chasse_clear_infos_affichage_cache((int) $chasse_id);
    }

    wp_send_json_success(['deleted' => $total_deleted]);
}
add_action('wp_ajax_cta_reset_stats', 'cta_reset_stats');

function cta_toggle_site_protection() {
    if (!current_user_can('administrator')) {
        wp_send_json_error(__('Non autorisé', 'chassesautresor-com'));
    }

    check_ajax_referer('cta_site_protection', 'nonce');

    $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? '1' : '0';
    update_option('ca_site_password_enabled', $enabled);

    wp_send_json_success(['enabled' => $enabled]);
}
add_action('wp_ajax_cta_toggle_site_protection', 'cta_toggle_site_protection');


/**
 * Charge le script de la carte Développement sur les pages Mon Compte.
 */
function charger_script_developpement_card() {
    if (preg_match('#^/mon-compte(?:/|$|\\?)#', $_SERVER['REQUEST_URI'] ?? '')) {
        wp_enqueue_script(
            'developpement-card',
            get_stylesheet_directory_uri() . '/assets/js/developpement-card.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/developpement-card.js'),
            true
        );
        wp_localize_script('developpement-card', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'charger_script_developpement_card');

function charger_script_site_protection_card() {
    if (preg_match('#^/mon-compte(?:/|$|\\?)#', $_SERVER['REQUEST_URI'] ?? '')) {
        wp_enqueue_script(
            'site-protection-card',
            get_stylesheet_directory_uri() . '/assets/js/site-protection-card.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/site-protection-card.js'),
            true
        );
        wp_localize_script(
            'site-protection-card',
            'siteProtectionCard',
            [
                'ajax_url'    => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('cta_site_protection'),
                'activated'   => __('Activé', 'chassesautresor-com'),
                'deactivated' => __('Désactivé', 'chassesautresor-com'),
            ]
        );
    }
}
add_action('wp_enqueue_scripts', 'charger_script_site_protection_card');

function charger_script_reset_stats_card() {
    if (preg_match('#^/mon-compte(?:/|$|\\?)#', $_SERVER['REQUEST_URI'] ?? '')) {
        wp_enqueue_script(
            'reset-stats-card',
            get_stylesheet_directory_uri() . '/assets/js/reset-stats-card.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/reset-stats-card.js'),
            true
        );
        wp_localize_script('reset-stats-card', 'resetStatsCard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cta_reset_stats'),
            'confirm'  => __('Confirmez-vous la réinitialisation des statistiques ?', 'chassesautresor-com'),
            'success'  => __('Statistiques effacées.', 'chassesautresor-com'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'charger_script_reset_stats_card');

// ==================================================
// 📦 TABLEAU ORGANISATEURS EN CRÉATION
// ==================================================
/**
 * Récupère la liste des organisateurs en cours de création.
 *
 * @return array[] Tableau des données trié du plus récent au plus ancien.
 */
function recuperer_organisateurs_en_creation() {
    if (!current_user_can('administrator')) {
        return [];
    }

    $users   = get_users(['role' => ROLE_ORGANISATEUR_CREATION]);
    $entries = [];

    foreach ($users as $user) {
        $organisateur_id = get_organisateur_from_user($user->ID);
        if (!$organisateur_id) {
            continue;
        }

        $date_creation = get_post_field('post_date', $organisateur_id);
        $chasses       = get_chasses_en_creation($organisateur_id);
        if (empty($chasses)) {
            continue;
        }

        $chasse_id  = (int) $chasses[0];
        $nb_enigmes = count(recuperer_enigmes_associees($chasse_id));

        $entries[] = [
            'date_creation'      => $date_creation,
            'organisateur_titre' => get_the_title($organisateur_id),
            'chasse_id'          => $chasse_id,
            'chasse_titre'       => get_the_title($chasse_id),
            'nb_enigmes'         => $nb_enigmes,
        ];
    }

    usort($entries, function ($a, $b) {
        return strtotime($b['date_creation']) <=> strtotime($a['date_creation']);
    });

    return $entries;
}

/**
 * Affiche les tableaux des organisateurs en création.
 */
function afficher_tableau_organisateurs_en_creation() {
    $liste = recuperer_organisateurs_en_creation();
    if (empty($liste)) {
        echo '<p>Aucun organisateur en création.</p>';
        return;
    }

    echo '<table class="stats-table"><tbody>';

    foreach ($liste as $entry) {
        echo '<tr>';
        echo '<td>' . esc_html($entry['organisateur_titre']) . '</td>';
        echo '<td><a href="' . esc_url(get_permalink($entry['chasse_id'])) . '">' . esc_html($entry['chasse_titre']) . '</a></td>';
        echo '<td>' . intval($entry['nb_enigmes']) . ' énigmes</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    $oldest = end($liste);
    echo '<table class="stats-table">';
    echo '<caption>+ Ancienne création</caption><tbody>';
    echo '<tr>';
    echo '<td>' . esc_html($oldest['organisateur_titre']) . '</td>';
    echo '<td><a href="' . esc_url(get_permalink($oldest['chasse_id'])) . '">' . esc_html($oldest['chasse_titre']) . '</a></td>';
    echo '<td>' . intval($oldest['nb_enigmes']) . ' énigmes</td>';
    echo '</tr></tbody></table>';
}

/**
 * Récupère les organisateurs avec statut pending.
 *
 * @return array[] Liste des données des organisateurs en attente.
 */
function recuperer_organisateurs_pending()
{
    if (!current_user_can('administrator')) {
        return [];
    }

    $query = new WP_Query([
        'post_type'      => 'organisateur',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ]);

    $resultats = [];

    foreach ($query->posts as $organisateur_id) {
        $titre     = get_the_title($organisateur_id);
        $permalink = get_permalink($organisateur_id);

        $users     = (array) get_field('utilisateurs_associes', $organisateur_id);
        $user_id   = $users ? intval(reset($users)) : null;
        $user_name = '';
        $user_link = '';
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $user_name = $user->display_name;
                $user_link = get_edit_user_link($user_id);
            }
        }

        verifier_ou_mettre_a_jour_cache_complet($organisateur_id);

        $chasses = new WP_Query([
            'post_type'      => 'chasse',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'pending', 'draft'],
            'meta_query'     => [
                [
                    'key'     => 'chasse_cache_organisateur',
                    'value'   => '"' . strval($organisateur_id) . '"',
                    'compare' => 'LIKE',
                ],
            ],
            'fields'         => 'ids',
        ]);

        if ($chasses->have_posts()) {
            foreach ($chasses->posts as $chasse_id) {
                verifier_ou_mettre_a_jour_cache_complet($chasse_id);

                $date_creation = get_post_field('post_date', $chasse_id);
                $chasse_titre  = get_the_title($chasse_id);
                $chasse_link   = get_permalink($chasse_id);
                $enigmes       = recuperer_enigmes_associees($chasse_id);
                $nb_enigmes    = count($enigmes);
                $statut        = get_field('chasse_cache_statut_validation', $chasse_id);

                $pending_validation = ($statut === 'en_attente');
                $pending_attempts   = false;
                foreach ($enigmes as $enigme_id) {
                    $mode = enigme_normaliser_mode_validation(get_field('enigme_mode_validation', $enigme_id));
                    if ($mode === 'manuelle' && compter_tentatives_en_attente($enigme_id) > 0) {
                        $pending_attempts = true;
                        break;
                    }
                }

                $resultats[] = [
                    'organisateur_id'        => $organisateur_id,
                    'organisateur_titre'     => $titre,
                    'organisateur_permalink' => $permalink,
                    'user_id'                => $user_id,
                    'user_name'              => $user_name,
                    'user_link'              => $user_link,
                    'chasse_id'              => $chasse_id,
                    'chasse_titre'           => $chasse_titre,
                    'chasse_permalink'       => $chasse_link,
                    'nb_enigmes'             => $nb_enigmes,
                    'statut'                 => $statut,
                    'validation'             => $statut,
                    'pending_validation'     => $pending_validation,
                    'pending_attempts'       => $pending_attempts,
                    'date_creation'          => $date_creation,
                ];
            }
        } else {
            $date_creation = get_post_field('post_date', $organisateur_id);
            $resultats[]   = [
                'organisateur_id'        => $organisateur_id,
                'organisateur_titre'     => $titre,
                'organisateur_permalink' => $permalink,
                'user_id'                => $user_id,
                'user_name'              => $user_name,
                'user_link'              => $user_link,
                'chasse_id'              => null,
                'chasse_titre'           => '',
                'chasse_permalink'       => '',
                'nb_enigmes'             => 0,
                'statut'                 => '',
                'validation'             => '',
                'pending_validation'     => false,
                'pending_attempts'       => false,
                'date_creation'          => $date_creation,
            ];
        }
    }

    usort($resultats, function ($a, $b) {
        $timeA = strtotime($a['date_creation']);
        $timeB = strtotime($b['date_creation']);
        return $timeA === $timeB ? 0 : ($timeA < $timeB ? 1 : -1);
    });

    return $resultats;
}

/**
 * Affiche la liste des organisateurs et leurs chasses dans un tableau.
 *
 * @param array|null $liste Données pré-calculées.
 * @param int        $page  Page courante.
 * @param int        $per_page Nombre d'organisateurs par page.
 */
function afficher_tableau_organisateurs_pending(?array $liste = null, int $page = 1, int $per_page = ORGANISATEURS_PENDING_PER_PAGE): void
{
    if (null === $liste) {
        $liste = recuperer_organisateurs_pending();
    }
    if (empty($liste)) {
        echo '<p>' . esc_html__('Aucun organisateur.', 'chassesautresor-com') . '</p>';
        return;
    }

    $grouped = [];
    foreach ($liste as $entry) {
        $oid = $entry['organisateur_id'];
        if (!isset($grouped[$oid])) {
            $grouped[$oid] = [
                'organisateur_titre'     => $entry['organisateur_titre'],
                'organisateur_permalink' => $entry['organisateur_permalink'],
                'user_id'                => $entry['user_id'],
                'user_name'              => $entry['user_name'],
                'user_link'              => $entry['user_link'],
                'rows'                   => [],
            ];
        }
        $grouped[$oid]['rows'][] = $entry;
    }

    $total  = count($grouped);
    $pages  = max(1, (int) ceil($total / $per_page));
    $page   = max(1, min($page, $pages));
    $offset = ($page - 1) * $per_page;
    $grouped = array_slice($grouped, $offset, $per_page, true);

    echo '<div class="stats-table-wrapper" data-per-page="' . intval($per_page) . '">';
    echo '<table class="stats-table table-organisateurs">';
    echo '<thead><tr>';
    echo '<th scope="col">' . esc_html__('Organisateur', 'chassesautresor-com') . '</th>';
    echo '<th scope="col">' . esc_html__('Chasse', 'chassesautresor-com') . '</th>';
    echo '<th scope="col" data-format="etiquette"><span class="etiquette">' . esc_html__('Nb énigmes', 'chassesautresor-com') . '</span></th>';
    echo '<th scope="col">' . esc_html__('État', 'chassesautresor-com') . '</th>';
    echo '<th scope="col">' . esc_html__('Utilisateur', 'chassesautresor-com') . '</th>';
    echo '<th scope="col" data-col="date">' . esc_html__('Créé le', 'chassesautresor-com') . ' <span class="tri-date">&#9650;&#9660;</span></th>';
    echo '</tr></thead><tbody>';

    foreach ($grouped as $org) {
        $rows    = $org['rows'];
        $rowspan = count($rows);
        $first   = true;
        foreach ($rows as $row) {
            echo '<tr data-etat="' . esc_attr($row['statut']) . '" data-date="' . esc_attr($row['date_creation']) . '">';
            if ($first) {
                echo '<td rowspan="' . intval($rowspan) . '"><a href="' . esc_url($org['organisateur_permalink']) . '" target="_blank">' . esc_html($org['organisateur_titre']) . '</a></td>';
            }

            if ($row['chasse_id']) {
                $statut      = $row['statut'];
                $badge_class = 'statut-revision';

                switch ($statut) {
                    case 'valide':
                        $badge_class  = 'statut-en_cours';
                        $statut_label = __('valide', 'chassesautresor-com');
                        break;
                    case 'correction':
                        $statut_label = __('correction', 'chassesautresor-com');
                        break;
                    case 'en_attente':
                        $statut_label = __('en attente', 'chassesautresor-com');
                        break;
                    case 'creation':
                        $statut_label = __('création', 'chassesautresor-com');
                        break;
                    case 'banni':
                        $badge_class  = 'statut-termine';
                        $statut_label = __('banni', 'chassesautresor-com');
                        break;
                    default:
                        $statut_label = $statut;
                        break;
                }

                echo '<td class="col-chasse"><a href="' . esc_url($row['chasse_permalink']) . '">'
                    . esc_html($row['chasse_titre'])
                    . '</a></td>';
                echo '<td class="col-enigmes"><span class="etiquette">' . intval($row['nb_enigmes']) . '</span></td>';

                $warning   = $row['pending_validation'] || $row['pending_attempts'];
                $tooltip   = '';
                if ($warning) {
                    if ($row['pending_validation'] && $row['pending_attempts']) {
                        $tooltip = __('Demande de validation et tentatives manuelles en attente', 'chassesautresor-com');
                    } elseif ($row['pending_validation']) {
                        $tooltip = __('Demande de validation en attente', 'chassesautresor-com');
                    } else {
                        $tooltip = __('Tentatives manuelles en attente de réponse', 'chassesautresor-com');
                    }
                }

                echo '<td data-col="etat"><span class="badge-statut ' . esc_attr($badge_class) . '">' . esc_html($statut_label) . '</span>';
                if ($warning) {
                    echo '<span class="required" aria-hidden="true" title="' . esc_attr($tooltip) . '">*</span>';
                }
                echo '</td>';
            } else {
                echo '<td class="col-chasse">-</td><td class="col-enigmes"><span class="etiquette">-</span></td><td data-col="etat"></td>';
            }

            if ($first) {
                if ($org['user_id']) {
                    echo '<td rowspan="' . intval($rowspan) . '"><a href="' . esc_url($org['user_link']) . '" target="_blank">' . esc_html($org['user_name']) . '</a></td>';
                } else {
                    echo '<td rowspan="' . intval($rowspan) . '">-</td>';
                }
            }

            echo '<td>' . esc_html(date_i18n('d/m/y', strtotime($row['date_creation']))) . '</td>';
            echo '</tr>';
            $first = false;
        }
    }

    echo '</tbody></table>';
    echo cta_render_pager($page, $pages, 'organisateurs-pager');
    echo '</div>';
}

/**
 * Traite les actions de validation d'une chasse envoyées depuis l'interface admin.
 *
 * Les actions possibles sont :
 * - `valider`    : publie la chasse et les énigmes liées puis bascule l'organisateur
 * - `correction` : demande des modifications sans changer le statut WordPress
 * - `bannir`     : passe la chasse et ses énigmes en brouillon
 * - `supprimer`  : met la chasse à la corbeille et supprime ses énigmes
 *
 * Un nonce `validation_admin_nonce` doit être présent dans le formulaire.
 *
 * @return void
 */
function traiter_validation_chasse_admin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['validation_admin_action'])) {
        return;
    }

    if (!current_user_can('administrator')) {
        wp_die( __( 'Accès refusé.', 'chassesautresor-com' ) );
    }

    $chasse_id = isset($_POST['chasse_id']) ? intval($_POST['chasse_id']) : 0;
    $action    = sanitize_text_field($_POST['validation_admin_action']);

    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        wp_die( __( 'ID de chasse invalide.', 'chassesautresor-com' ) );
    }

    if (!isset($_POST['validation_admin_nonce']) || !wp_verify_nonce($_POST['validation_admin_nonce'], 'validation_admin_' . $chasse_id)) {
        wp_die( __( 'Nonce invalide.', 'chassesautresor-com' ) );
    }

    $enigmes          = recuperer_enigmes_associees($chasse_id);
    $organisateur_id  = get_organisateur_from_chasse($chasse_id);
    $users            = $organisateur_id ? (array) get_field('utilisateurs_associes', $organisateur_id) : [];
    $user_ids         = array_values(
        array_filter(
            array_map(
                function ($uid) {
                    return is_object($uid) ? intval($uid->ID) : intval($uid);
                },
                $users
            )
        )
    );
    $titre_chasse     = get_the_title($chasse_id);
    $url_chasse       = get_permalink($chasse_id);

    if ($action === 'valider') {
        wp_update_post([
            'ID'          => $chasse_id,
            'post_status' => 'publish',
        ]);

        $cache = get_field('champs_caches', $chasse_id) ?: [];
        $cache['chasse_cache_statut_validation'] = 'valide';
        update_field('champs_caches', $cache, $chasse_id);
        update_field('chasse_cache_statut_validation', 'valide', $chasse_id);
        mettre_a_jour_statuts_chasse($chasse_id);

        foreach ($enigmes as $eid) {
            wp_update_post(['ID' => $eid, 'post_status' => 'publish']);
            enigme_mettre_a_jour_etat_systeme($eid);
        }

        if ($organisateur_id) {
            if (get_post_status($organisateur_id) === 'pending') {
                wp_update_post([
                    'ID'          => $organisateur_id,
                    'post_status' => 'publish',
                ]);
            }

            $user_id = $user_ids ? $user_ids[0] : 0;
            if ($user_id) {
                $user = new WP_User($user_id);
                $user->add_role(ROLE_ORGANISATEUR);
                $user->remove_role(ROLE_ORGANISATEUR_CREATION);
            }
        }

        $flash = sprintf(
            __('Votre demande de validation pour la chasse « %s » a été acceptée.', 'chassesautresor-com'),
            esc_html($titre_chasse)
        );
        foreach ($user_ids as $uid) {
            myaccount_add_flash_message($uid, $flash, 'success');
        }

        envoyer_mail_chasse_validee($organisateur_id, $chasse_id);

    } elseif ($action === 'correction') {
        $cache = get_field('champs_caches', $chasse_id) ?: [];
        $cache['chasse_cache_statut_validation'] = 'correction';
        update_field('champs_caches', $cache, $chasse_id);
        update_field('chasse_cache_statut_validation', 'correction', $chasse_id);

        wp_update_post([
            'ID'          => $chasse_id,
            'post_status' => 'pending',
        ]);

        mettre_a_jour_statuts_chasse($chasse_id);

        $message = isset($_POST['validation_admin_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['validation_admin_message']))
            : '';

        foreach ($enigmes as $eid) {
            wp_update_post([
                'ID'          => $eid,
                'post_status' => 'pending',
            ]);
            update_field('enigme_cache_etat_systeme', 'bloquee_chasse', $eid);
        }

        envoyer_mail_demande_correction($organisateur_id, $chasse_id, $message);

        $flash = sprintf(
            __('Votre demande de validation pour la chasse « %s » nécessite des corrections.', 'chassesautresor-com'),
            '<a href="' . esc_url($url_chasse) . '">' . esc_html($titre_chasse) . '</a>'
        );
        if ($message !== '') {
            $flash .= '<br>' . sprintf(
                __('Message de l’administrateur : %s', 'chassesautresor-com'),
                nl2br(esc_html($message))
            );
        }
        $flash .= '<br>' . __('Une copie de ce message vous a été envoyée par email.', 'chassesautresor-com');
        foreach ($user_ids as $uid) {
            myaccount_add_persistent_message(
                $uid,
                'correction_chasse_' . $chasse_id,
                $flash,
                'warning',
                true,
                $chasse_id,
                true
            );
            $info_msg = sprintf(
                /* translators: %1$s and %2$s are anchor tags */
                __('Votre chasse est éligible à une %1$sdemande de validation%2$s.', 'chassesautresor-com'),
                '<a href="' . esc_url(get_permalink($chasse_id) . '#cta-validation-chasse') . '">',
                '</a>'
            );
            myaccount_add_persistent_message(
                $uid,
                'correction_info_chasse_' . $chasse_id,
                $info_msg,
                'info',
                false,
                $chasse_id,
                true
            );
        }

    } elseif ($action === 'bannir') {
        wp_update_post([
            'ID'          => $chasse_id,
            'post_status' => 'draft',
        ]);

        $cache = get_field('champs_caches', $chasse_id) ?: [];
        $cache['chasse_cache_statut_validation'] = 'banni';
        update_field('champs_caches', $cache, $chasse_id);
        update_field('chasse_cache_statut_validation', 'banni', $chasse_id);

        foreach ($enigmes as $eid) {
            wp_update_post(['ID' => $eid, 'post_status' => 'draft']);
        }

        envoyer_mail_chasse_bannie($organisateur_id, $chasse_id);

        $flash = sprintf(
            __('Votre chasse « %s » a été bannie.', 'chassesautresor-com'),
            esc_html($titre_chasse)
        );
        foreach ($user_ids as $uid) {
            myaccount_add_flash_message($uid, $flash, 'error');
        }

    } elseif ($action === 'supprimer') {
        foreach ($enigmes as $eid) {
            wp_delete_post($eid, true);
        }

        $images = get_attached_media('image', $chasse_id);
        foreach ($images as $attachment) {
            wp_delete_attachment($attachment->ID, true);
        }

        wp_trash_post($chasse_id);

        envoyer_mail_chasse_supprimee($organisateur_id, $chasse_id);

        $flash = sprintf(
            __('Votre chasse « %s » a été supprimée.', 'chassesautresor-com'),
            esc_html($titre_chasse)
        );
        foreach ($user_ids as $uid) {
            myaccount_add_flash_message($uid, $flash, 'error');
        }
    }

    // Après le traitement, rediriger systématiquement vers la liste des
    // organisateurs afin d'éviter une erreur 404 si la chasse n'existe plus.
    wp_safe_redirect(home_url('/mon-compte/organisateurs/'));
    exit;
}
add_action('admin_post_traiter_validation_chasse', 'traiter_validation_chasse_admin');

/**
 * Envoie un email à l'organisateur lorsqu'une chasse nécessite des corrections.
 *
 * @param int    $organisateur_id ID du CPT organisateur.
 * @param int    $chasse_id       ID de la chasse concernée.
 * @param string $message         Message saisi par l'administrateur.
 *
 * @return void
 */
function envoyer_mail_demande_correction(int $organisateur_id, int $chasse_id, string $message)
{
    if (!$organisateur_id || !$chasse_id) {
        return;
    }

    $admin_email = get_option('admin_email');
    $emails      = [];

    $acf_email = get_field('email_organisateur', $organisateur_id);
    if (is_array($acf_email)) {
        $acf_email = reset($acf_email);
    }
    if (is_string($acf_email) && is_email($acf_email)) {
        $emails[] = sanitize_email($acf_email);
    }

    $users = (array) get_field('utilisateurs_associes', $organisateur_id);
    foreach ($users as $uid) {
        $user_id = is_object($uid) ? $uid->ID : intval($uid);
        if ($user_id) {
            $user = get_user_by('ID', $user_id);
            if ($user && is_email($user->user_email)) {
                $emails[] = sanitize_email($user->user_email);
            }
        }
    }

    if (!$emails) {
        $emails[] = $admin_email;
    }

    $titre_chasse = get_the_title($chasse_id);
    $url_chasse   = get_permalink($chasse_id);

    $subject_raw = '[Chasses au Trésor] Corrections requises pour votre chasse';

    $body  = '<div style="font-family:Arial,sans-serif;font-size:14px;">';
    $body .= '<p>Bonjour,</p>';
    $body .= '<p>Votre chasse <a href="' . esc_url($url_chasse) . '">' . esc_html($titre_chasse) . '</a> nécessite des corrections pour être validée.</p>';
    if ($message !== '') {
        $body .= '<p><em>Message de l\'administrateur :</em><br>' . nl2br(esc_html($message)) . '</p>';
    }
    $body .= '<p>Une fois les modifications effectuées, soumettez à nouveau votre chasse depuis votre espace organisateur.</p>';
    $body .= '<p style="margin-top:2em;">L’équipe chassesautresor.com</p>';
    $body .= '</div>';

    $headers = [];

    $from_filter = function ($name) use ($organisateur_id) {
        $titre = get_the_title($organisateur_id);
        return $titre ?: $name;
    };
    add_filter('wp_mail_from_name', $from_filter, 10, 1);

    cta_send_email($emails, $subject_raw, $body, $headers);
    cta_send_email($admin_email, $subject_raw, $body, $headers);
    remove_filter('wp_mail_from_name', $from_filter, 10);

}

/**
 * Envoie un email informant l'organisateur que sa chasse a été bannie.
 *
 * @param int $organisateur_id ID du CPT organisateur.
 * @param int $chasse_id       ID de la chasse concernée.
 *
 * @return void
 */
function envoyer_mail_chasse_bannie(int $organisateur_id, int $chasse_id)
{
    if (!$organisateur_id || !$chasse_id) {
        return;
    }

    $email = get_field('email_organisateur', $organisateur_id);
    if (is_array($email)) {
        $email = reset($email);
    }

    if (!is_string($email) || !is_email($email)) {
        $email = get_option('admin_email');
    }

    $admin_email = get_option('admin_email');
    $titre_chasse = get_the_title($chasse_id);

    $subject_raw = '[Chasses au Trésor] Chasse bannie';

    $body  = '<p>' . esc_html__('Bonjour,', 'chassesautresor-com') . '</p>';
    $body .= '<p>' . sprintf(esc_html__('Votre chasse "%s" a été bannie par l\'administrateur.', 'chassesautresor-com'), esc_html($titre_chasse)) . '</p>';

    $headers = [
        'Bcc: ' . $admin_email,
    ];

    $from_filter = function ($name) use ($organisateur_id) {
        $titre = get_the_title($organisateur_id);
        return $titre ?: $name;
    };
    add_filter('wp_mail_from_name', $from_filter, 10, 1);

    cta_send_email($email, $subject_raw, $body, $headers);
    remove_filter('wp_mail_from_name', $from_filter, 10);
}

/**
 * Envoie un email informant l'organisateur que sa chasse a été supprimée.
 *
 * @param int $organisateur_id ID du CPT organisateur.
 * @param int $chasse_id       ID de la chasse concernée.
 *
 * @return void
 */
function envoyer_mail_chasse_supprimee(int $organisateur_id, int $chasse_id)
{
    if (!$organisateur_id || !$chasse_id) {
        return;
    }

    $email = get_field('email_organisateur', $organisateur_id);
    if (is_array($email)) {
        $email = reset($email);
    }

    if (!is_string($email) || !is_email($email)) {
        $email = get_option('admin_email');
    }

    $admin_email = get_option('admin_email');
    $titre_chasse = get_the_title($chasse_id);

    $subject_raw = '[Chasses au Trésor] Chasse supprimée';

    $body  = '<p>' . esc_html__('Bonjour,', 'chassesautresor-com') . '</p>';
    $body .= '<p>' . sprintf(esc_html__('Votre chasse "%s" a été supprimée par l\'administrateur.', 'chassesautresor-com'), esc_html($titre_chasse)) . '</p>';

    $headers = [
        'Bcc: ' . $admin_email,
    ];

    $from_filter = function ($name) use ($organisateur_id) {
        $titre = get_the_title($organisateur_id);
        return $titre ?: $name;
    };
    add_filter('wp_mail_from_name', $from_filter, 10, 1);

    cta_send_email($email, $subject_raw, $body, $headers);
    remove_filter('wp_mail_from_name', $from_filter, 10);
}

/**
 * Envoie un email informant l'organisateur que sa chasse est validée.
 *
 * @param int $organisateur_id ID du CPT organisateur.
 * @param int $chasse_id       ID de la chasse concernée.
 *
 * @return void
 */
function envoyer_mail_chasse_validee(int $organisateur_id, int $chasse_id)
{
    if (!$organisateur_id || !$chasse_id) {
        return;
    }

    $emails = [];

    $acf_email = get_field('email_organisateur', $organisateur_id);
    if (is_array($acf_email)) {
        $acf_email = reset($acf_email);
    }
    if (is_string($acf_email) && is_email($acf_email)) {
        $emails[] = sanitize_email($acf_email);
    }

    $users = (array) get_field('utilisateurs_associes', $organisateur_id);
    foreach ($users as $uid) {
        $user_id = is_object($uid) ? $uid->ID : intval($uid);
        if ($user_id) {
            $user = get_user_by('ID', $user_id);
            if ($user && is_email($user->user_email)) {
                $emails[] = sanitize_email($user->user_email);
            }
        }
    }

    if (!$emails) {
        $emails[] = get_option('admin_email');
    }

    $emails = array_unique($emails);

    $admin_email = get_option('admin_email');
    $titre_chasse = get_the_title($chasse_id);
    $url_chasse   = get_permalink($chasse_id);
    $url_qr_code  = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . rawurlencode($url_chasse);

    $subject_raw = '✅ Votre chasse est maintenant validée !';

    $body  = '<p>Bonjour,</p>';
    $body .= '<p>Votre chasse <strong>&laquo;' . esc_html($titre_chasse) . '&raquo;</strong> a été <strong>validée avec succès</strong> par notre équipe 🎉<br>';
    $body .= 'Elle est désormais <strong>accessible aux joueurs</strong>.</p>';
    $body .= '<hr>';
    $body .= '<p>🔗 <strong>Lien vers votre chasse :</strong><br>';
    $body .= '<a href="' . esc_url($url_chasse) . '" target="_blank">' . esc_html($url_chasse) . '</a></p>';
    $body .= '<p>📲 <strong>QR code à partager :</strong><br>';
    $body .= '<img src="' . esc_url($url_qr_code) . '" alt="QR code vers la chasse" style="max-width:200px; height:auto; display:block; margin-top:1em;">';
    $body .= '<br><a href="' . esc_url($url_qr_code) . '" download>Télécharger le QR code</a></p>';
    $body .= '<hr>';
    $body .= '<p>Nous vous souhaitons une belle aventure, et restons à votre écoute si besoin.<br>';
    $body .= 'À très bientôt,<br>L’équipe <strong>Chasses au Trésor</strong></p>';

    $headers = [
        'Bcc: ' . $admin_email,
    ];

    $from_filter = function ($name) use ($organisateur_id) {
        $titre = get_the_title($organisateur_id);
        return $titre ?: $name;
    };
    add_filter('wp_mail_from_name', $from_filter, 10, 1);

    cta_send_email($emails, $subject_raw, $body, $headers);
    remove_filter('wp_mail_from_name', $from_filter, 10);
}


