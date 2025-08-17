<?php
/**
 * chassesautresor.com Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package chassesautresor.com
 * @since 1.0.0
 */
defined( 'ABSPATH' ) || exit;
/**
 * Define Constants
 */
define( 'CHILD_THEME_CHASSESAUTRESOR_COM_VERSION', '1.0.0' );

/**
 * Charge le domaine de traduction du thème enfant.
 */
function cta_load_textdomain() {
    load_child_theme_textdomain( 'chassesautresor-com', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'cta_load_textdomain' );


/**
 * Chargement des styles du thème parent et enfant avec prise en charge d'Astra.
 */
add_action('wp_enqueue_scripts', function () {
    $theme_uri  = get_stylesheet_directory_uri();
    $theme_path = get_stylesheet_directory();

    // 🎨 Chargement du style du thème parent (Astra)
    wp_enqueue_style('astra-style', get_template_directory_uri() . '/style.css');

    // Détermine l'environnement via WP_ENVIRONMENT_TYPE ou une constante dédiée.
    $env            = defined('CHASSESAUTRESOR_ENV') ? CHASSESAUTRESOR_ENV : wp_get_environment_type();
    $is_edition_env = 'edition' === $env;

    if ($is_edition_env) {
        wp_enqueue_style(
            'mon-theme-enfant-style',
            $theme_uri . '/style.css',
            ['astra-style'],
            filemtime($theme_path . '/style.css')
        );

        $css_uri  = $theme_uri . '/assets/css/';
        $css_path = $theme_path . '/assets/css/';

        // 📂 Liste des fichiers CSS organisés
        $styles = [
            'grid'               => 'grid.css',
            'layout'             => 'layout.css',
            'components'         => 'components.css',
            'modal-bienvenue'    => 'modal-bienvenue.css',
            'general-style'      => 'general.css',
            'chasse-style'       => 'chasse.css',
            'enigme-style'       => 'enigme.css',
            'gamification-style' => 'gamification.css',
            'cartes-style'       => 'cartes.css',
            'organisateurs'      => 'organisateurs.css',
            'edition'            => 'edition.css',
            'mon-compte'         => 'mon-compte.css',
            'commerce-style'     => 'commerce.css',
            'home'               => 'home.css',
        ];

        // ✅ Enregistre les styles avec gestion du cache
        foreach ($styles as $handle => $file) {
            wp_register_style($handle, $css_uri . $file, [], filemtime($css_path . $file));
        }

        // 🚀 Chargement des styles communs
        $common_styles = [
            'grid',
            'layout',
            'components',
            'modal-bienvenue',
            'general-style',
            'chasse-style',
            'enigme-style',
            'gamification-style',
            'cartes-style',
            'organisateurs',
            'commerce-style',
            'home',
        ];

        foreach ($common_styles as $handle) {
            wp_enqueue_style($handle);
        }

        // 📌 Styles conditionnels
        if (
            is_singular(['organisateur', 'chasse', 'enigme']) ||
            (
                (is_account_page() || preg_match('#^/mon-compte(?:/|$|\\?)#', $_SERVER['REQUEST_URI'] ?? '')) &&
                is_user_logged_in()
            )
        ) {
            wp_enqueue_style('edition');
        }

        if (is_account_page() || preg_match('#^/mon-compte(?:/|$|\\?)#', $_SERVER['REQUEST_URI'] ?? '')) {
            wp_enqueue_style('mon-compte');
        }
    } else {
        $dist_file = '/dist/style.min.css';
        wp_enqueue_style(
            'chassesautresor-style',
            $theme_uri . $dist_file,
            ['astra-style'],
            filemtime($theme_path . $dist_file)
        );
    }

    $script_dir = $theme_uri . '/assets/js/';
    if (is_account_page() && is_user_logged_in()) {
        wp_enqueue_script(
            'myaccount',
            $script_dir . 'myaccount.js',
            [],
            filemtime($theme_path . '/assets/js/myaccount.js'),
            true
        );
        wp_localize_script('myaccount', 'ctaMyAccount', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }
});



// ----------------------------------------------------------
// 📂 Chargement des fichiers fonctionnels organisés
// ----------------------------------------------------------

$inc_path = get_stylesheet_directory() . '/inc/';

require_once $inc_path . 'constants.php';
require_once $inc_path . 'utils.php';
require_once $inc_path . 'PointsRepository.php';

require_once $inc_path . 'shortcodes-init.php';
require_once $inc_path . 'enigme-functions.php';
require_once $inc_path . 'user-functions.php';
require_once $inc_path . 'chasse-functions.php';
require_once $inc_path . 'gamify-functions.php';
require_once $inc_path . 'utils/titres.php';
require_once $inc_path . 'statut-functions.php';
require_once $inc_path . 'admin-functions.php';
require_once $inc_path . 'organisateur-functions.php';
//require_once $inc_path . 'stat-functions.php';
require_once $inc_path . 'access-functions.php';
require_once $inc_path . 'relations-functions.php';
require_once $inc_path . 'layout-functions.php';
require_once $inc_path . 'myaccount-functions.php';
require_once $inc_path . 'utils/liens.php';
require_once $inc_path . 'chasse/stats.php';
require_once $inc_path . 'organisateur/stats.php';

require_once $inc_path . 'edition/edition-core.php';
require_once $inc_path . 'edition/edition-organisateur.php';
require_once $inc_path . 'edition/edition-chasse.php';
require_once $inc_path . 'edition/edition-enigme.php';
require_once $inc_path . 'edition/edition-securite.php';



/**
 * Injecte automatiquement `acf_form_head()` pour les fiches chasse.
 *
 * Ce hook est nécessaire pour que les panneaux latéraux basés sur `acf_form()`
 * (notamment la description WYSIWYG) fonctionnent correctement en front-end.
 *
 * - Il doit être exécuté avant toute sortie HTML.
 * - Il active la prise en charge des redirections, messages de succès, et champs ACF dynamiques.
 * - ACF recommande son appel dans le `header.php`, mais ici on l'injecte proprement via `wp_head` uniquement pour les chasses.
 *
 * 💡 À terme, cette fonction pourrait être déplacée dans un fichier dédié (ex : acf-hooks.php)
 *
 * @hook wp_head
 */
add_action('wp_head', 'forcer_acf_form_head_chasse', 0);
function forcer_acf_form_head_chasse()
{
    if (!is_singular('chasse') || !function_exists('acf_form_head')) {
        return;
    }

    $post_id = get_queried_object_id();

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    acf_form_head();
}


/**
 * 🔁 TÂCHES QUOTIDIENNES INTERNES – SYNCHRONISATION DU CACHE DES ÉNIGMES
 *
 * Cette fonction est appelée par le cron quotidien global du site pour assurer la cohérence
 * entre les chasses et les énigmes qui leur sont réellement associées.
 *
 * Elle utilise la fonction `verifier_et_synchroniser_cache_enigmes_si_autorise()` qui déclenche,
 * si nécessaire, une correction du champ ACF `chasse_cache_enigmes` (relation).
 *
 * 🔧 Cette fonction est placée exceptionnellement dans `functions.php` (racine du thème)
 * car elle fait partie du cœur d'exécution automatique du site, mais ne s’intègre à aucun module métier isolé.
 *
 * 🧱 Si d’autres tâches automatiques internes sont ajoutées à terme (purge, maintenance, synchronisation...),
 * cette logique pourra être déplacée dans un fichier dédié (`inc/cron-functions.php`) pour allègement.
 *
 * @return void
 */
function tache_cron_synchroniser_cache_enigmes(): void {
    $chasses = get_posts([
        'post_type'      => 'chasse',
        'post_status'    => ['publish', 'pending', 'draft'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($chasses as $chasse_id) {
        verifier_et_synchroniser_cache_enigmes_si_autorise($chasse_id);
    }
}

