<?php
/**
 * Base layout for "Mon Compte" pages.
 *
 * The shell provides the sidebar. This template only renders the account content.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$content_template      = $GLOBALS['myaccount_content_template'] ?? null;
$current_user          = wp_get_current_user();
$display_name          = $current_user->ID ? $current_user->display_name : get_bloginfo('name');
$last_active_formatted = '';

if ($current_user->ID) {
    $last_active_raw = get_user_meta($current_user->ID, 'wc_last_active', true);
    if ($last_active_raw) {
        if (is_numeric($last_active_raw)) {
            $last_active_timestamp = absint($last_active_raw);
        } else {
            $last_active_timestamp = strtotime($last_active_raw);
        }

        if (!empty($last_active_timestamp)) {
            $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
            $date_format = strpos($locale, 'fr_') === 0 ? 'd/m/Y' : 'n/j/Y';

            if (function_exists('wp_date')) {
                $last_active_formatted = wp_date($date_format, $last_active_timestamp);
            } else {
                $last_active_formatted = date_i18n($date_format, $last_active_timestamp);
            }
        }
    }
}

get_header();
?>
<div class="myaccount-layout">
    <div class="myaccount-main">
        <?php
        $page_title    = '';
        $page_greeting = '';
        $single_hunt_home = function_exists('cat_is_single_hunt_mode') && cat_is_single_hunt_mode();
        $is_profile_root = $single_hunt_home
            && function_exists('is_account_page')
            && is_account_page()
            && function_exists('is_wc_endpoint_url')
            && !is_wc_endpoint_url()
            && empty($_GET['section']);
        $is_player_front_home = !empty($GLOBALS['cat_account_home_on_front'])
            && function_exists('cat_single_hunt_home_audience')
            && cat_single_hunt_home_audience() === 'joueur';
        if (
            $is_profile_root
            || is_wc_endpoint_url('edit-account')
            || is_wc_endpoint_url('orders')
            || is_wc_endpoint_url('edit-address')
        ) {
            $page_title = __('Profil', 'chassesautresor-com');
        } elseif (is_wc_endpoint_url('tentatives')) {
            $page_title = __('Tentatives', 'chassesautresor-com');
        } elseif (!empty($GLOBALS['cat_account_home_on_front'])) {
            $page_greeting = __('Bienvenue', 'chassesautresor-com');
            $page_title    = $display_name;
        } elseif (is_account_page() && empty($_GET['section'])) {
            $page_greeting = __('Bienvenue', 'chassesautresor-com');
            $page_title    = $display_name;
        }
        if (!$is_player_front_home && ($page_title || $last_active_formatted)) :
            ?>
            <header class="myaccount-header">
                <?php if ($page_title) : ?>
                    <h1 class="myaccount-title">
                        <?php if ($page_greeting) : ?>
                            <span class="myaccount-title-greeting"><?php echo esc_html($page_greeting); ?></span>
                        <?php endif; ?>
                        <span class="myaccount-title-text"><?php echo esc_html($page_title); ?></span>
                    </h1>
                <?php endif; ?>
                <?php if ($last_active_formatted) : ?>
                    <p class="myaccount-last-active">
                        <span class="meta-label">
                            <?php esc_html_e('Dernière connexion :', 'chassesautresor-com'); ?>
                        </span>
                        <span class="meta-value"><?php echo esc_html($last_active_formatted); ?></span>
                    </p>
                <?php endif; ?>
            </header>
            <?php
        endif;
        ?>
        <main class="myaccount-content">
            <?php
            if (!empty($GLOBALS['cat_account_home_on_front']) && function_exists('cat_render_cockpit_demo_notice')) {
                cat_render_cockpit_demo_notice();
            }
            if ($content_template && file_exists($content_template)) {
                include $content_template;
            } else {
                if (function_exists('woocommerce_account_content')) {
                    woocommerce_account_content();
                } else {
                    echo '<p>' . esc_html__('Content not found.', 'chassesautresor-com') . '</p>';
                }
            }
            ?>
        </main>
    </div>
</div>

<?php
get_footer();
