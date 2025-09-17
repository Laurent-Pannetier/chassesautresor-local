<?php
defined( 'ABSPATH' ) || exit;

// ==================================================
// 📚 SOMMAIRE DU FICHIER
// ==================================================
//
// 1. 📦 TEMPLATES UTILISATEURS
//    - Routage personnalisé de /mon-compte/ vers des templates dédiés
//
// 2. 📦 MODIFICATION AVATAR EN FRONT
//    - Gestion complète de l’upload, affichage et remplacement de l’avatar utilisateur
//
// 3. 📦 TUILES UTILISATEUR
//    - Affichage dynamique des éléments liés à l’utilisateur (commandes WooCommerce)
//
// 4. 📦 ATTRIBUTION DE RÔLE
//     - Attribution des rôles oragnisateurs (création)
//
// 5. 📣 MESSAGES IMPORTANTS
//    - Affichage centralisé des messages clés de l'espace "Mon Compte"

// 6. 📡 AJAX ADMIN SECTIONS
//    - Chargement dynamique des pages d'administration dans "Mon Compte"
//
// ==================================================
// 📦 TEMPLATES UTILISATEURS
// ==================================================
/**
 * 🔹 ajouter_rewrite_rules → Déclarer les règles de réécriture pour les URL personnalisées de l’espace "Mon Compte".
 * 🔹 ajouter_query_vars → Déclarer les variables de requête personnalisées associées aux URL de l’espace utilisateur.
 * 🔹 charger_template_utilisateur → Charger dynamiquement un template spécifique selon l’URL dans "Mon Compte".
 * 🔹 modifier_titre_onglet → Modifier dynamiquement le titre de la page dans l'onglet du navigateur.
 * 🔹 is_woocommerce_account_page → Vérifier si la page actuelle est une sous-page WooCommerce dans "Mon Compte".
 */


/**
 * Charge dynamiquement un template spécifique en fonction de l'URL sous /mon-compte/.
 *
 * Cette fonction intercepte l'affichage des templates WordPress et remplace le fichier de template 
 * si l'URL demandée correspond à une page spécifique de l'espace utilisateur (ex: organisateurs, administrateurs).
 *
 * @param string $template Le chemin du template par défaut déterminé par WordPress.
 * @return string Le chemin du fichier de template personnalisé ou le template par défaut.
 */
function ajouter_rewrite_rules() {
    add_rewrite_rule('^mon-compte/statistiques/?$', 'index.php?mon_compte_statistiques=1', 'top');
    add_rewrite_rule('^mon-compte/outils/?$', 'index.php?mon_compte_outils=1', 'top');
    add_rewrite_rule('^mon-compte/organisation/?$', 'index.php?mon_compte_organisation=1', 'top');
}
add_action('init', 'ajouter_rewrite_rules');

/**
 * ➕ Déclare les variables de requête personnalisées associées aux URL de l’espace utilisateur.
 *
 * Ces variables sont nécessaires pour que WordPress reconnaisse les URL réécrites
 * comme valides et les transmette aux hooks `template_include`.
 *
 * @param array $vars Tableau des variables de requête connues.
 * @return array Tableau enrichi avec les nouvelles variables personnalisées.
 *
 * @hook query_vars
 */
function ajouter_query_vars($vars) {
    $vars[] = 'mon_compte_statistiques';
    $vars[] = 'mon_compte_outils';
    $vars[] = 'mon_compte_organisation';
    return $vars;
}
add_filter('query_vars', 'ajouter_query_vars');

/**
 * 📦 Charge dynamiquement un template spécifique pour certaines URL de l'espace utilisateur.
 *
 * Cette fonction intercepte l’inclusion du template principal WordPress (`template_include`)
 * et remplace le fichier de template par défaut si l’URL correspond à l’une des URL personnalisées
 * définies via les règles de réécriture.
 *
 * - Ignore les endpoints WooCommerce pour éviter les conflits.
 * - Vérifie l’existence des fichiers de template personnalisés dans `/templates/admin/`.
 * - Active un logging (`error_log`) utile au débogage.
 *
 * @param string $template Le chemin du template par défaut déterminé par WordPress.
 * @return string Le chemin du fichier de template personnalisé ou le template par défaut.
 *
 * @hook template_include
 */
function charger_template_utilisateur($template) {
    // Récupération et nettoyage de l'URL demandée
    $request_uri = trim($_SERVER['REQUEST_URI'], '/');

    // Vérification pour éviter les conflits avec WooCommerce
    if (is_wc_endpoint_url()) {
        return $template;
    }

    if ($request_uri === 'mon-compte/points' || $request_uri === 'mon-compte/points/') {
        wp_redirect(home_url('/mon-compte/?section=points'));
        exit;
    }

    if ($request_uri === 'mon-compte/chasses' || $request_uri === 'mon-compte/chasses/') {
        wp_redirect(home_url('/mon-compte/?section=chasses'));
        exit;
    }
    
    // Associe chaque URL à un fichier de contenu spécifique
    $mapping_templates = array(
        'mon-compte/organisateurs'        => 'content-organisateurs.php',
        'mon-compte/organisateurs/'       => 'content-organisateurs.php', // Variante avec /
        'mon-compte/statistiques'         => 'content-statistiques.php',
        'mon-compte/outils'               => 'content-outils.php',
        'mon-compte/organisation'         => 'content-organisation.php',
        'mon-compte/organisation/'        => 'content-organisation.php',
    );

    $admin_paths = array(
        'mon-compte/organisateurs',
        'mon-compte/organisateurs/',
        'mon-compte/statistiques',
        'mon-compte/outils',
    );

    // Vérifie si l'URL correspond à un contenu personnalisé
    if (array_key_exists($request_uri, $mapping_templates)) {
        if (in_array($request_uri, $admin_paths, true)) {
            $section       = str_replace('mon-compte/', '', rtrim($request_uri, '/'));
            $redirect_path = '/mon-compte/';

            if (current_user_can('administrator')) {
                $redirect_path .= '?section=' . $section;
            }

            wp_redirect(home_url($redirect_path));
            exit;
        }

        $content_template = get_stylesheet_directory() . '/templates/myaccount/' . $mapping_templates[$request_uri];

        if (!file_exists($content_template)) {
            cat_debug('Fichier de contenu introuvable : ' . $content_template);
        } else {
            // Stocke le chemin pour l'injection dans le layout
            $GLOBALS['myaccount_content_template'] = $content_template;
        }

        // Retourne le layout commun pour les pages "Mon Compte"
        return get_stylesheet_directory() . '/templates/myaccount/layout.php';
    }

    // Retourne le template par défaut si aucune correspondance n'est trouvée
    return $template;
}
add_filter('template_include', 'charger_template_utilisateur');

/**
 * Check if the current request corresponds to the organisation account page.
 *
 * @return bool
 */
function myaccount_is_organisation_page() {
    if ((int) get_query_var('mon_compte_organisation') === 1) {
        return true;
    }

    if (empty($_SERVER['REQUEST_URI'])) {
        return false;
    }

    $path = parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH);

    if ($path === null || $path === false) {
        return false;
    }

    $request_path = trim($path, '/');

    return $request_path === 'mon-compte/organisation';
}

/**
 * Add a dedicated body class for the organisation account page.
 *
 * @param array $classes The body classes collected by WordPress.
 *
 * @return array
 */
function myaccount_add_organisation_body_class(array $classes) {
    if (!myaccount_is_organisation_page()) {
        return $classes;
    }

    $additional_classes = array(
        'myaccount-organisation-page',
        'woocommerce-account',
        'woocommerce-page',
    );

    foreach ($additional_classes as $class) {
        if (!in_array($class, $classes, true)) {
            $classes[] = $class;
        }
    }

    return $classes;
}
add_filter('body_class', 'myaccount_add_organisation_body_class');

/**
 * Ensure the organisation page is treated as a WooCommerce account page.
 *
 * @param bool $is_account_page Whether WooCommerce already considers the view an account page.
 *
 * @return bool
 */
function myaccount_mark_organisation_account_page($is_account_page) {
    if ($is_account_page) {
        return true;
    }

    return myaccount_is_organisation_page();
}
add_filter('woocommerce_is_account_page', 'myaccount_mark_organisation_account_page');

/**
 * Modifier dynamiquement le titre de la page dans l'onglet du navigateur
 *
 * @param string $title Le titre actuel.
 * @return string Le titre modifié.
 */
function modifier_titre_onglet($title) {
    global $wp;
    $current_url = trim($wp->request, '/');

    // Définition des titres pour chaque page
    $page_titles = [
        'mon-compte/statistiques'  => __('Statistiques - Chasses au Trésor', 'chassesautresor-com'),
        'mon-compte/outils'        => __('Outils - Chasses au Trésor', 'chassesautresor-com'),
        'mon-compte/organisateurs' => __('Organisateur - Chasses au Trésor', 'chassesautresor-com'),
    ];

    // Titre spécifique pour /mon-compte/?section=points
    if ($current_url === 'mon-compte' && (($_GET['section'] ?? '') === 'points')) {
        return __('Points - Chasses au Trésor', 'chassesautresor-com');
    }

    if ($current_url === 'mon-compte' && (($_GET['section'] ?? '') === 'chasses')) {
        return __('Chasses - Chasses au Trésor', 'chassesautresor-com');
    }

    if ($current_url === 'mon-compte/organisation') {
        $user = wp_get_current_user();
        if ($user && $user->ID && function_exists('get_organisateur_from_user')) {
            $organisateur_id = get_organisateur_from_user((int) $user->ID);
            if ($organisateur_id && function_exists('get_the_title')) {
                $organisateur_title = get_the_title($organisateur_id);
                if ($organisateur_title) {
                    return wp_strip_all_tags($organisateur_title);
                }
            }
        }

        return __('Mon organisation', 'chassesautresor-com');
    }

    // Si l’URL correspond à une page définie, modifier le titre
    if (isset($page_titles[$current_url])) {
        return $page_titles[$current_url];
    }

    return $title; // Conserver le titre par défaut si l'URL ne correspond pas
}
add_filter('pre_get_document_title', 'modifier_titre_onglet');

/**
 * Vérifie si la page actuelle est une page WooCommerce spécifique dans "Mon Compte".
 *
 * Cette fonction analyse l'URL actuelle et détermine si elle correspond à l'une
 * des pages WooCommerce spécifiques où le contenu du compte WooCommerce doit être affiché.
 *
 * Liste des pages WooCommerce prises en compte :

 *
 * @return bool True si la page actuelle est une page WooCommerce autorisée, False sinon.
 */
function is_woocommerce_account_page() {
    // Récupérer l'URL actuelle
    $current_url = $_SERVER['REQUEST_URI'];

    // Liste des pages WooCommerce où afficher woocommerce_account_content()
    $pages_woocommerce = [
        '/mon-compte/commandes/',
        '/mon-compte/voir-commandes/',
        '/mon-compte/modifier-adresse/',
        '/mon-compte/modifier-compte/',
        '/mon-compte/telechargements/',
        '/mon-compte/moyens-paiement/',
        '/mon-compte/lost-password/',
        '/mon-compte/customer-logout/'
    ];

    // Vérifier si l'URL actuelle correspond à une page WooCommerce autorisée
    foreach ($pages_woocommerce as $page) {
        if (strpos($current_url, $page) === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Rename WooCommerce "orders" endpoint title to "Vos commandes".
 *
 * @param string $title Original title.
 * @return string Modified title.
 */
function ca_orders_endpoint_title($title)
{
    return __('Vos commandes', 'chassesautresor-com');
}
add_filter('woocommerce_endpoint_orders_title', 'ca_orders_endpoint_title');

/**
 * Rename "edit-account" endpoint title to "Profil".
 *
 * @param string $title Original title.
 * @return string Modified title.
 */
function ca_profile_endpoint_title($title)
{
    return __('Profil', 'chassesautresor');
}
add_filter('woocommerce_endpoint_edit-account_title', 'ca_profile_endpoint_title');

// ==================================================
// 📣 IMPORTANT MESSAGES
// ==================================================
/**
 * Store a persistent important message for the given user.
 *
 * @param int         $user_id       User identifier.
 * @param string      $key           Unique message key.
 * @param string      $message       Message to store.
 * @param string      $type          Message type (success, info, error, warning).
 * @param bool        $dismissible   Whether the user can dismiss the message.
 * @param int         $chasse_scope  Optional hunt scope.
 * @param bool        $include_enigmes Whether to include enigmas in the scope.
 * @param string|null $message_key   Optional translation key.
 * @param string|null $locale        Optional locale for the message.
 * @param int|null    $expires       Expiration as timestamp or duration in seconds.
 *
 * @return int Message identifier or 0 on failure.
 */
function myaccount_add_persistent_message(
    int $user_id,
    string $key,
    string $message,
    string $type = 'info',
    bool $dismissible = false,
    int $chasse_scope = 0,
    bool $include_enigmes = false,
    ?string $message_key = null,
    ?string $locale = null,
    ?int $expires = null
): int {
    global $wpdb;

    $repo   = new UserMessageRepository($wpdb);
    $payload = [
        'key'         => $key,
        'text'        => $message,
        'type'        => $type,
        'dismissible' => $dismissible,
    ];

    if ($message_key !== null) {
        $payload['message_key'] = $message_key;
    }

    if ($locale !== null) {
        $payload['locale'] = $locale;
    }

    if ($chasse_scope > 0) {
        $payload['chasse_scope']   = $chasse_scope;
        $payload['include_enigmes'] = $include_enigmes;
    }

    // Remove existing message with the same key if present.
    $existing = $repo->get($user_id, 'persistent', null);
    foreach ($existing as $row) {
        $data = json_decode($row['message'], true);
        if (is_array($data) && ($data['key'] ?? '') === $key) {
            $repo->delete((int) $row['id']);
        }
    }

    $expiresAt = null;
    if ($expires !== null) {
        $now = (int) current_time('timestamp');
        if ($expires > $now) {
            $expiresAt = gmdate('c', $expires);
        } else {
            $expiresAt = gmdate('c', $now + $expires);
        }
    }

    $message_id = $repo->insert(
        $user_id,
        wp_json_encode($payload),
        'persistent',
        $expiresAt,
        $locale
    );

    if (0 === $message_id) {
        error_log(
            sprintf(
                'myaccount_add_persistent_message failed for user %d and key %s: %s',
                $user_id,
                $key,
                $wpdb->last_error
            )
        );
    }

    return $message_id;
}

/**
 * Remove a persistent message for the given user.
 *
 * @param int    $user_id User identifier.
 * @param string $key     Message key.
 *
 * @return void
 */
function myaccount_remove_persistent_message(int $user_id, string $key): void
{
    global $wpdb;

    $repo     = new UserMessageRepository($wpdb);
    $messages = $repo->get($user_id, 'persistent', null);

    foreach ($messages as $row) {
        $data = json_decode($row['message'], true);
        if (is_array($data) && ($data['key'] ?? '') === $key) {
            $repo->delete((int) $row['id']);
        }
    }
}

/**
 * Remove correction messages for a given hunt for all related users.
 *
 * @param int $chasse_id Hunt identifier.
 *
 * @return void
 */
function myaccount_clear_correction_message(int $chasse_id): void
{
    $current          = get_current_user_id();
    $organisateur_id  = get_organisateur_from_chasse($chasse_id);
    $users            = $organisateur_id ? (array) get_field('utilisateurs_associes', $organisateur_id) : [];

    $user_ids = array_map(
        static function ($uid) {
            return is_object($uid) ? (int) $uid->ID : (int) $uid;
        },
        $users
    );

    $user_ids[] = $current;

    if ($organisateur_id) {
        $author_id = (int) get_post_field('post_author', $organisateur_id);
        if ($author_id) {
            $user_ids[] = $author_id;
        }
    }

    $user_ids = array_unique($user_ids);

    foreach ($user_ids as $uid) {
        myaccount_remove_persistent_message($uid, 'correction_chasse_' . $chasse_id);
        myaccount_remove_persistent_message($uid, 'correction_info_chasse_' . $chasse_id);
    }
}

/**
 * Ensure the validation info message is stored for eligible hunts.
 *
 * Adds a persistent informational message when an organizer can request
 * validation for a hunt but has not yet dismissed the message. The message is
 * scoped to the hunt and its riddles.
 *
 * @return void
 */
function myaccount_maybe_add_validation_message(): void
{
    if (!is_user_logged_in() || !is_singular(['chasse', 'enigme'])) {
        return;
    }

    $post_id = get_queried_object_id();
    $chasse_id = get_post_type($post_id) === 'chasse'
        ? $post_id
        : (function_exists('recuperer_id_chasse_associee')
            ? (int) recuperer_id_chasse_associee($post_id)
            : 0);

    if (!$chasse_id) {
        return;
    }

    verifier_ou_mettre_a_jour_cache_complet($chasse_id);

    $user_id = get_current_user_id();
    if (!peut_valider_chasse($chasse_id, $user_id)) {
        myaccount_remove_persistent_message($user_id, 'correction_info_chasse_' . $chasse_id);
        return;
    }

    $key = 'correction_info_chasse_' . $chasse_id;

    global $wpdb;
    $repo     = new UserMessageRepository($wpdb);
    $existing = $repo->get($user_id, 'persistent', null);
    foreach ($existing as $row) {
        $data = json_decode($row['message'], true);
        if (is_array($data) && ($data['key'] ?? '') === $key) {
            if (!isset($data['chasse_scope']) || !array_key_exists('include_enigmes', $data)) {
                $repo->delete((int) $row['id']);
                break;
            }
            return;
        }
    }

    $info_msg = sprintf(
        /* translators: %1$s and %2$s are anchor tags */
        __('Votre chasse est éligible à une %1$sdemande de validation%2$s.', 'chassesautresor-com'),
        '<a href="' . esc_url(get_permalink($chasse_id) . '#cta-validation-chasse') . '">',
        '</a>'
    );

    myaccount_add_persistent_message(
        $user_id,
        $key,
        $info_msg,
        'info',
        false,
        $chasse_id,
        true
    );
}
add_action('template_redirect', 'myaccount_maybe_add_validation_message');

/**
 * Retrieve persistent important messages for the given user.
 *
 * @param int $user_id User identifier.
 *
 * @return array<int, array{key:string,text:string,message_key:string,locale:string,type:string,dismissible:bool}>
*/
function myaccount_get_persistent_messages(int $user_id): array
{
    global $wpdb;

    $repo   = new UserMessageRepository($wpdb);
    $rows   = $repo->get($user_id, 'persistent', false);
    $messages = [];
    foreach ($rows as $row) {
        $data = json_decode($row['message'], true);
        if (is_array($data)) {
            if (!empty($row['locale'])) {
                $data['locale'] = $row['locale'];
            }
            $key = isset($data['key']) ? (string) $data['key'] : (string) $row['id'];
            $messages[$key] = $data;
        }
    }

    $current_id   = get_queried_object_id();
    $current_type = get_post_type($current_id);
    $current_chasse = 0;
    if ($current_type === 'chasse') {
        $current_chasse = $current_id;
    } elseif ($current_type === 'enigme' && function_exists('recuperer_id_chasse_associee')) {
        $current_chasse = (int) recuperer_id_chasse_associee($current_id);
    }

    $tentatives = [];
    foreach ($messages as $key => $msg) {
        $item = is_array($msg)
            ? $msg
            : ['text' => $msg, 'type' => 'info', 'dismissible' => false];
        if (strpos($key, 'tentative_') === 0) {
            $text = $item['text'] ?? '';
            if (preg_match('/<a[^>]*>.*?<\/a>/', $text, $matches)) {
                $tentatives[] = $matches[0];
            } else {
                $tentatives[] = $text;
            }
            unset($messages[$key]);
        } else {
            $messages[$key] = $item;
        }
    }

    $output = [];
    foreach ($messages as $key => $msg) {
        if (is_array($msg) && isset($msg['text'])) {
            $scope = isset($msg['chasse_scope']) ? (int) $msg['chasse_scope'] : 0;
            $include_enigmes = !empty($msg['include_enigmes']);
            if ($scope) {
                if ($scope !== $current_chasse) {
                    continue;
                }
                if (!$include_enigmes && $current_type === 'enigme') {
                    continue;
                }
            } elseif ($current_type === 'enigme') {
                continue;
            }

            $output[] = [
                'key'         => (string) $key,
                'text'        => (string) $msg['text'],
                'message_key' => isset($msg['message_key']) ? (string) $msg['message_key'] : '',
                'locale'      => isset($msg['locale']) ? (string) $msg['locale'] : '',
                'type'        => isset($msg['type']) ? (string) $msg['type'] : 'info',
                'dismissible' => !empty($msg['dismissible']),
            ];
        }
    }

    if (!empty($tentatives)) {
        if (count($tentatives) === 1) {
            $output[] = [
                'text' => sprintf(
                    __(
                        'Votre demande de résolution de l\'énigme %s est en cours de traitement. '
                        . 'Vous recevrez une notification dès que votre demande sera traitée.',
                        'chassesautresor-com'
                    ),
                    $tentatives[0]
                ),
                'type' => 'info',
            ];
        } else {
            $links = array_map(
                function ($anchor) {
                    return str_replace('<a ', '<a class="etiquette" ', $anchor);
                },
                $tentatives
            );

            $output[] = [
                'text' => sprintf(
                    __(
                        'Vos demandes de résolution d\'énigmes sont en cours de traitement : %s. '
                        . 'Vous recevrez une notification dès que vos demandes seront traitées.',
                        'chassesautresor-com'
                    ),
                    implode(' ', $links)
                ),
                'type' => 'info',
            ];
        }
    }

    return $output;
}

/**
 * Store a flash message for the given user.
 *
 * @param int    $user_id     User identifier.
 * @param string $message     Message to store.
 * @param string $type        Message type (success, info, error, warning).
 * @param bool   $dismissible Whether the user can dismiss the message.
 *
 * @return void
 */
function myaccount_add_flash_message(
    int $user_id,
    string $message,
    string $type = 'info',
    bool $dismissible = false
): void {
    global $wpdb;

    $repo = new UserMessageRepository($wpdb);
    $repo->insert(
        $user_id,
        wp_json_encode([
            'text'        => $message,
            'type'        => $type,
            'dismissible' => $dismissible,
        ]),
        'flash'
    );
}

/**
 * Retrieve and clear flash messages for the given user.
 *
 * @param int $user_id User identifier.
 *
 * @return array<int, array{text:string,type:string,dismissible:bool}>
 */
function myaccount_get_flash_messages(int $user_id): array
{
    global $wpdb;

    $repo = new UserMessageRepository($wpdb);
    $rows = $repo->get($user_id, 'flash', false);
    $messages = [];

    foreach ($rows as $row) {
        $data = json_decode($row['message'], true);
        if (is_array($data) && isset($data['text'])) {
            $messages[] = [
                'text'        => (string) $data['text'],
                'type'        => isset($data['type']) ? (string) $data['type'] : 'info',
                'dismissible' => !empty($data['dismissible']),
            ];
        }
        $repo->delete((int) $row['id']);
    }

    return $messages;
}

/**
 * Get pre-formatted HTML for the important message section in My Account pages.
 *
 * @return string
 */
function myaccount_get_important_messages(): string
{
    $current_user_id = get_current_user_id();
    $messages = array_merge(
        myaccount_get_persistent_messages($current_user_id),
        myaccount_get_flash_messages($current_user_id)
    );
    $flash = '';

    if (isset($_GET['points_modifies']) && $_GET['points_modifies'] === '1') {
        $flash = '<p class="flash flash--success">' . __('Points mis à jour avec succès.', 'chassesautresor') . '</p>';
    }

    if (current_user_can('administrator')) {
        if (function_exists('recuperer_organisateurs_pending')) {
            $pending = array_filter(
                recuperer_organisateurs_pending(),
                function ($entry) {
                    return !empty($entry['chasse_id']) && $entry['validation'] === 'en_attente';
                }
            );

            if (!empty($pending)) {
                $links = array_map(
                    function ($entry) {
                        $url   = esc_url(get_permalink($entry['chasse_id']));
                        $title = esc_html(get_the_title($entry['chasse_id']));
                        return '<a href="' . $url . '">' . $title . '</a>';
                    },
                    $pending
                );

                $label = count($pending) > 1
                    ? __('Chasses à valider :', 'chassesautresor')
                    : __('Chasse à valider :', 'chassesautresor');

                $messages[] = [
                    'text' => $label . ' ' . implode(', ', $links),
                    'type' => 'info',
                ];
            }
        }

        global $wpdb;
        $repo            = new PointsRepository($wpdb);
        $pendingRequests = $repo->getConversionRequests(null, 'pending');

        if (!empty($pendingRequests)) {
            $url = esc_url(add_query_arg('section', 'points', home_url('/mon-compte/')));
            $messages[] = [
                'text' => sprintf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    __('Vous avez des %1$sdemandes de conversion%2$s en attente.', 'chassesautresor-com'),
                    '<a href="' . $url . '">',
                    '</a>'
                ),
                'type' => 'info',
            ];
        }
    }

    if (est_organisateur()) {
        $current_user_id   = get_current_user_id();
        $organisateur_id   = get_organisateur_from_user($current_user_id);

        global $wpdb;
        $repo       = new PointsRepository($wpdb);
        $pendingOwn = $repo->getConversionRequests($current_user_id, 'pending');
        if (!empty($pendingOwn)) {
            $conversion_url = $organisateur_id
                ? esc_url(
                    add_query_arg(
                        [
                            'edition' => 'open',
                            'onglet'  => 'revenus',
                        ],
                        get_permalink($organisateur_id)
                    )
                )
                : esc_url(home_url('/mon-compte/?section=points'));

            $messages[] = [
                'text' => sprintf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    __('Vous avez une %1$sdemande de conversion%2$s en attente de règlement.', 'chassesautresor'),
                    '<a href="' . $conversion_url . '">',
                    '</a>'
                ),
                'type' => 'info',
            ];
        }

        if ($organisateur_id) {
            $pendingChasses = get_posts([
                'post_type'   => 'chasse',
                'post_status' => ['publish', 'pending'],
                'numberposts' => -1,
                'fields'      => 'ids',
                'meta_query'  => [
                    [
                        'key'     => 'chasse_cache_organisateur',
                        'value'   => '"' . $organisateur_id . '"',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key'   => 'chasse_cache_statut_validation',
                        'value' => 'en_attente',
                    ],
                ],
            ]);

            if (!empty($pendingChasses)) {
                foreach ($pendingChasses as $chasse_id) {
                    $url   = esc_url(get_permalink($chasse_id));
                    $title = esc_html(get_the_title($chasse_id));
                    $messages[] = [
                        'text' => sprintf(
                            /* translators: %s: hunt title with link */
                            __('Demande pour %s en cours de traitement', 'chassesautresor-com'),
                            '<a href="' . $url . '">' . $title . '</a>'
                        ),
                        'type' => 'info',
                    ];
                }
            }
        }
    }

    if (empty($messages) && $flash === '') {
        return '';
    }

    $output = array_map(
        function ($msg) {
            $type        = $msg['type'] ?? 'info';
            $text        = $msg['text'] ?? '';
            if (!empty($msg['message_key'])) {
                if (!empty($msg['locale']) && function_exists('switch_to_locale')) {
                    switch_to_locale($msg['locale']);
                    $text = __($msg['message_key'], 'chassesautresor-com');
                    restore_previous_locale();
                } else {
                    $text = __($msg['message_key'], 'chassesautresor-com');
                }
            }
            $dismissible = !empty($msg['dismissible']) && !empty($msg['key']);

            switch ($type) {
                case 'success':
                    $class = 'message-succes';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
                case 'error':
                    $class = 'message-erreur';
                    $aria  = 'role="alert" aria-live="assertive"';
                    break;
                case 'warning':
                    $class = 'message-info';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
                default:
                    $class = 'message-info';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
            }

            $button = '';
            if ($dismissible) {
                $button = ' <button type="button" class="message-close" data-key="'
                    . esc_attr($msg['key'])
                    . '" aria-label="'
                    . esc_attr__('Supprimer ce message', 'chassesautresor-com')
                    . '">×</button>';
            }

            return '<p class="' . esc_attr($class) . '" ' . $aria . '>' . $text . $button . '</p>';
        },
        $messages
    );

    return $flash . implode('', $output);
}

// ==================================================
// 📊 TENTATIVES UTILISATEUR
// ==================================================
/**
 * Display the Tentatives table on the My Account dashboard.
 *
 * @return void
 */
function ca_render_dashboard_tentatives(): void
{
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = (int) get_current_user_id();
    if ($user_id <= 0) {
        return;
    }

    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();

    wp_enqueue_script(
        'pager',
        $uri . '/assets/js/core/pager.js',
        [],
        filemtime($dir . '/assets/js/core/pager.js'),
        true
    );

    wp_enqueue_script(
        'tentatives-pager',
        $uri . '/assets/js/tentatives-pager.js',
        ['pager'],
        filemtime($dir . '/assets/js/tentatives-pager.js'),
        true
    );

    global $wpdb;

    $table   = $wpdb->prefix . 'enigme_tentatives';
    $pending = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND resultat = 'attente' AND traitee = 0",
        $user_id
    ));
    $total   = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
        $user_id
    ));
    $success = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND resultat = 'bon'",
        $user_id
    ));

    if ($total <= 0) {
        return;
    }

    $per_page   = 10;
    $page       = max(1, (int) ($_GET['tentatives-page'] ?? 1));
    $offset     = ($page - 1) * $per_page;
    $tentatives = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, p.post_title FROM {$table} t JOIN {$wpdb->posts} p ON t.enigme_id = p.ID WHERE t.user_id = %d ORDER BY t.date_tentative DESC LIMIT %d OFFSET %d",
        $user_id,
        $per_page,
        $offset
    ));

    $pages = (int) ceil($total / $per_page);

    ob_start();
    ?>
    <section class="myaccount-tentatives">
        <h3><?php esc_html_e('Tentatives', 'chassesautresor-com'); ?></h3>
        <div class="table-header">
            <?php if ($pending > 0) : ?>
            <span class="stat-badge"><?php printf(esc_html(_n('%d tentative en attente', '%d tentatives en attente', $pending, 'chassesautresor-com')), $pending); ?></span>
            <?php endif; ?>
            <span class="stat-badge"><?php printf(esc_html(_n('%d tentative', '%d tentatives', $total, 'chassesautresor-com')), $total); ?></span>
            <?php if ($success > 0) : ?>
            <span class="stat-badge" style="color:var(--color-success);">
                <?php printf(esc_html(_n('%d bonne réponse', '%d bonnes rponses', $success, 'chassesautresor-com')), $success); ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="stats-table-wrapper" data-per-page="<?php echo esc_attr($per_page); ?>">
            <table class="stats-table tentatives-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'chassesautresor-com'); ?></th>
                        <th><?php esc_html_e('Chasse', 'chassesautresor-com'); ?></th>
                        <th><?php esc_html_e('Énigme', 'chassesautresor-com'); ?></th>
                        <th><?php esc_html_e('Proposition', 'chassesautresor-com'); ?></th>
                        <th><?php esc_html_e('Résultat', 'chassesautresor-com'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tentatives as $tent) : ?>
                    <tr>
                        <?php $chasse_id = (int) recuperer_id_chasse_associee($tent->enigme_id); ?>
                        <td><?php echo esc_html(mysql2date('d/m/Y H:i', $tent->date_tentative)); ?></td>
                        <td>
                            <?php if ($chasse_id) : ?>
                            <a href="<?php echo esc_url(get_permalink($chasse_id)); ?>">
                                <?php echo esc_html(get_the_title($chasse_id)); ?>
                            </a>
                            <?php else : ?>
                            &mdash;
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($tent->post_title); ?></td>
                        <?php echo cta_render_proposition_cell($tent->reponse_saisie ?? ''); ?>
                        <?php
                        $result = $tent->resultat;
                        $class  = 'etiquette-error';
                        if ($result === 'bon') {
                            $class = 'etiquette-success';
                        } elseif ($result === 'attente') {
                            $class = 'etiquette-pending';
                        }
                        ?>
                        <td>
                            <span class="etiquette <?php echo esc_attr($class); ?>">
                                <?php echo esc_html__($result, 'chassesautresor-com'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php echo cta_render_pager($page, $pages, 'tentatives-pager', ['data-param' => 'tentatives-page', 'data-section' => '']); ?>
        </div>
    </section>
    <?php
    echo ob_get_clean();
}
add_action('woocommerce_account_dashboard', 'ca_render_dashboard_tentatives', 20);

// ==================================================
// 📡 AJAX ADMIN SECTIONS
// ==================================================
/**
 * Load My Account sections via AJAX.
 *
 * @return void
 */
function ca_load_admin_section()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
    }

    $section = sanitize_key($_GET['section'] ?? '');
    $allowed = [
        'points'        => ['template' => 'content-points.php', 'cap' => 'read'],
        'chasses'       => ['template' => 'content-chasses.php', 'cap' => 'read'],
        'organisateurs' => ['template' => 'content-organisateurs.php', 'cap' => 'administrator'],
        'statistiques'  => ['template' => 'content-statistiques.php', 'cap' => 'administrator'],
        'outils'        => ['template' => 'content-outils.php', 'cap' => 'administrator'],
    ];

    if (!isset($allowed[$section])) {
        wp_send_json_error(['message' => __('Section not found', 'chassesautresor-com')], 404);
    }

    $cap = $allowed[$section]['cap'];
    if ($cap !== 'read' && !current_user_can($cap)) {
        wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
    }

    ob_start();
    $template = get_stylesheet_directory() . '/templates/myaccount/' . $allowed[$section]['template'];
    if (file_exists($template)) {
        include $template;
    }
    $html = ob_get_clean();

    wp_send_json_success([
        'html'     => $html,
        'messages' => myaccount_get_important_messages(),
    ]);
}
add_action('wp_ajax_cta_load_admin_section', 'ca_load_admin_section');

/**
 * Dismiss a persistent message via AJAX.
 *
 * @return void
 */
function ca_dismiss_message(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
    }

    $key = sanitize_key($_POST['key'] ?? '');
    if ($key === '') {
        wp_send_json_error(['message' => __('Clé de message invalide.', 'chassesautresor-com')], 400);
    }

    myaccount_remove_persistent_message(get_current_user_id(), $key);
    remove_site_message($key);

    wp_send_json_success();
}
add_action('wp_ajax_cta_dismiss_message', 'ca_dismiss_message');

// ==================================================
// 📦 MODIFICATION AVATAR EN FRONT
// ==================================================
/**
 * 🔹 upload_user_avatar → Traiter l’upload d’un avatar utilisateur via AJAX.
 * 🔹 autoriser_avatars_upload → Autoriser les formats d’image pour l’upload d’avatars personnalisés.
 * 🔹 remplacer_avatar_utilisateur → Remplacer l’avatar par défaut par celui défini par l’utilisateur.
 * 🔹 charger_script_avatar_upload → Charger le script JS d’upload uniquement sur les pages commençant par "/mon-compte/".
 */


/**
 * 🖼️ Traiter l'upload d'un avatar utilisateur via AJAX.
 *
 * Cette fonction est appelée via l'action AJAX `wp_ajax_upload_user_avatar` côté authentifié.
 * Elle permet à un utilisateur connecté de téléverser une image personnalisée en tant qu'avatar.
 *
 * Étapes :
 * - Vérifie que l’utilisateur est connecté.
 * - Valide la présence, le format (JPG, PNG, GIF, WEBP) et la taille du fichier (2 Mo max).
 * - Gère le téléversement du fichier via `wp_handle_upload()`.
 * - Enregistre l’URL dans la meta `user_avatar`.
 * - Retourne une réponse JSON avec l’URL du nouvel avatar.
 *
 * 🔐 Sécurité :
 * - Seuls les utilisateurs connectés peuvent utiliser ce point d’entrée.
 * - La validation du type MIME et de la taille empêche les uploads malveillants.
 *
 * 💡 Remarque :
 * - Cette fonction n'utilise pas `media_handle_upload()` mais `wp_handle_upload()` directement,
 *   ce qui est plus léger mais ne crée pas de pièce jointe dans la médiathèque.
 *
 * @return void Réponse JSON (succès ou erreur) via `wp_send_json_*`.
 *
 * @hook wp_ajax_upload_user_avatar
 */
add_action('wp_ajax_upload_user_avatar', 'upload_user_avatar');
function upload_user_avatar() {
    // 🛑 Vérifie si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Vous devez être connecté.']);
    }

    $user_id = get_current_user_id();

    // 📌 Vérifie si un fichier a été envoyé
    if (!isset($_FILES['avatar'])) {
        wp_send_json_error(['message' => 'Aucun fichier reçu.']);
    }

    $file = $_FILES['avatar'];
    $max_size = 2 * 1024 * 1024; // 🔹 2 Mo
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    // 🛑 Vérifie la taille du fichier
    if ($file['size'] > $max_size) {
        wp_send_json_error(['message' => 'Taille dépassée : 2 Mo max.']);
    }

    // 🛑 Vérifie le type MIME du fichier
    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error(['message' => 'Format non autorisé. Formats autorisés : JPG, PNG, GIF, WEBP.']);
    }

    // 📌 Déplacer et enregistrer l’image
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $upload = wp_handle_upload($file, ['test_form' => false]);

    // ✅ Vérification de `$upload` avant d'aller plus loin
    if (!$upload || isset($upload['error'])) {
        wp_send_json_error(['message' => 'Erreur lors du téléversement : ' . ($upload['error'] ?? 'Inconnue')]);
    }

    // 📌 Mettre à jour l’avatar dans la base de données
    update_user_meta($user_id, 'user_avatar', $upload['url']);

    // 📌 Répondre en JSON avec l'URL de l'image
    $avatar_url = get_user_meta($user_id, 'user_avatar', true);
    wp_send_json_success([
        'message' => 'Image mise à jour avec succès.',
        'new_avatar_url' => esc_url($avatar_url)
    ]);
}


/**
 * 📌 Autorise les formats d'image pour l'upload des avatars.
 *
 * @param array $mimes Liste des types MIME autorisés.
 * @return array Liste mise à jour avec les formats acceptés.
 */
function autoriser_avatars_upload($mimes) {
    $mimes['jpg'] = 'image/jpeg';
    $mimes['jpeg'] = 'image/jpeg';
    $mimes['png'] = 'image/png';
    $mimes['gif'] = 'image/gif';
    $mimes['webp'] = 'image/webp'; // ✅ Ajout du format WebP
    return $mimes;
}
add_filter('upload_mimes', 'autoriser_avatars_upload');

/**
 * 📌 Remplace l'avatar par défaut par l'avatar personnalisé de l'utilisateur.
 *
 * @param string $avatar Code HTML de l'avatar par défaut.
 * @param mixed $id_or_email Identifiant ou email de l'utilisateur.
 * @param int $size Taille de l'avatar.
 * @param string $default Avatar par défaut si aucun avatar personnalisé n'est trouvé.
 * @param string $alt Texte alternatif de l'avatar.
 * @return string HTML de l'avatar personnalisé ou avatar par défaut.
 */
function remplacer_avatar_utilisateur($avatar, $id_or_email, $size, $default, $alt) {
    $user_id = 0;

    // 🔹 Vérifie si l'entrée est un ID, un objet ou un email
    if (is_numeric($id_or_email)) {
        $user_id = $id_or_email;
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user_id = $id_or_email->user_id;
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    }

    // 📌 Vérifie si l'utilisateur a un avatar enregistré en base
    $avatar_url = get_user_meta($user_id, 'user_avatar', true);

    if (!empty($avatar_url)) {
        return "<img src='" . esc_url($avatar_url) . "' alt='" . esc_attr($alt) . "' width='{$size}' height='{$size}' class='avatar avatar-{$size} photo' />";
    }

    return $avatar;
}
add_filter('get_avatar', 'remplacer_avatar_utilisateur', 10, 5);

/**
 * 📌 Charge le fichier JavaScript uniquement sur les pages commençant par "/mon-compte/"
 */
function charger_script_avatar_upload() {
    if (strpos($_SERVER['REQUEST_URI'], '/mon-compte/') === 0) {
        wp_enqueue_script(
            'avatar-upload',
            get_stylesheet_directory_uri() . '/assets/js/avatar-upload.js',
            [],
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'charger_script_avatar_upload');



// ==================================================
// 📦 TUILES UTILISATEUR
// ==================================================
/**
 * 🔹 afficher_commandes_utilisateur → Récupérer et afficher les 4 dernières commandes d’un utilisateur WooCommerce sous forme de tableau.
 */


/**
 * Récupère et affiche les 3 dernières commandes d'un utilisateur WooCommerce sous forme de tableau.
 *
 * @param int $user_id ID de l'utilisateur connecté.
 * @param int $limit Nombre de commandes à afficher (par défaut : 4).
 * @return string HTML du tableau des commandes ou une chaîne vide si aucune commande.
 */
function afficher_commandes_utilisateur($user_id, $limit = 4) {
    if (!$user_id || !class_exists('WooCommerce')) {
        return '';
    }

    $customer_orders = wc_get_orders([
        'limit'    => $limit,
        'customer' => $user_id,
        'status'   => ['wc-completed'], // Commandes valides
        'orderby'  => 'date',
        'order'    => 'DESC'
    ]);
    
    if (empty($customer_orders)) {
        return ''; // Ne rien afficher si aucune commande
    }

    ob_start(); // Capture l'affichage HTML
    ?>
    <table class="stats-table">
        <tbody>
            <?php foreach ($customer_orders as $order) : ?>
                <?php
                $order_id = $order->get_id();
                $order_date = wc_format_datetime($order->get_date_created(), 'd/m/Y');
                $items = $order->get_items();
                $first_item = reset($items); // Récupère le premier produit de la commande
                $product_name = $first_item ? $first_item->get_name() : 'Produit inconnu';
                ?>
                <tr>
                    <td>#<?php echo esc_html($order_id); ?></td>
                    <td><?php echo esc_html($product_name); ?></td>
                    <td><?php echo esc_html($order_date); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean(); // Retourne le HTML capturé
}



// ==================================================
// 📦 ATTRIBUTION DE RÔLE
// ==================================================
/**
 * 🔹 ajouter_role_organisateur_creation() → Ajoute le rôle "organisateur_creation" à un abonné après la création CONFIRMÉE d'un CPT "organisateur"
 */

/**
 * 📌 Ajoute le rôle "organisateur_creation" à un abonné après la création CONFIRMÉE d'un CPT "organisateur".
 *
 * - Vérifie que l'utilisateur est "subscriber" avant de modifier son rôle.
 * - Vérifie que le post n'est pas en mode "auto-draft" (création en cours).
 * - Ne touche AUCUN autre rôle (admin, organisateur...).
 *
 * @param int      $post_id ID du post enregistré.
 * @param WP_Post  $post    Objet du post.
 * @param bool     $update  Indique si le post est mis à jour ou nouvellement créé.
 * @return void
 */
function ajouter_role_organisateur_creation($post_id, $post, $update) {
    // 🔹 Vérifie que le post est bien un CPT "organisateur"
    if ($post->post_type !== 'organisateur') {
        return;
    }

    // 🔹 Vérifie si le post est un "auto-draft" (pas encore enregistré par l'utilisateur)
    if ($post->post_status === 'auto-draft') {
        return;
    }

    $user_id = get_current_user_id();
    $user = new WP_User($user_id);

    // 🔹 Vérifie si l'utilisateur est "subscriber" avant de lui attribuer "organisateur_creation"
    if (in_array('subscriber', $user->roles, true)) {
        $user->add_role(ROLE_ORGANISATEUR_CREATION); // ✅ Ajoute le rôle sans retirer "subscriber"
        cat_debug("✅ L'utilisateur $user_id a maintenant aussi le rôle 'organisateur_creation'.");
    }
}
add_action('save_post', 'ajouter_role_organisateur_creation', 10, 3);

