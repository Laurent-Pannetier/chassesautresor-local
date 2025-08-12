<?php
/**
 * Base layout for "Mon Compte" pages.
 *
 * This template defines the common structure and injects dynamic content
 * provided via the global variable `$myaccount_content_template`.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$content_template = $GLOBALS['myaccount_content_template'] ?? null;
$current_user     = wp_get_current_user();
$display_name     = $current_user->ID ? $current_user->display_name : get_bloginfo('name');
$show_nav         = is_user_logged_in();
$current_path     = trim($_SERVER['REQUEST_URI'], '/');
?>
<div class="myaccount-layout">
    <aside class="myaccount-sidebar">
        <div class="myaccount-brand">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <?php echo esc_html($display_name); ?>
            </a>
        </div>
        <?php if ($show_nav) : ?>
        <nav class="dashboard-nav">
            <?php
            $nav_items = array(
                array(
                    'endpoint' => 'dashboard',
                    'label'    => __('Accueil', 'chassesautresor'),
                    'icon'     => 'fas fa-home',
                    'url'      => wc_get_account_endpoint_url('dashboard'),
                    'active'   => is_account_page() && !is_wc_endpoint_url(),
                ),
                array(
                    'endpoint' => 'orders',
                    'label'    => __('Points', 'chassesautresor'),
                    'icon'     => 'fas fa-coins',
                    'url'      => wc_get_account_endpoint_url('orders'),
                    'active'   => is_wc_endpoint_url('orders'),
                ),
                array(
                    'endpoint' => 'edit-account',
                    'label'    => __('Profil', 'chassesautresor'),
                    'icon'     => 'fas fa-user',
                    'url'      => wc_get_account_endpoint_url('edit-account'),
                    'active'   => is_wc_endpoint_url('edit-account'),
                ),
            );

            foreach ($nav_items as $item) {
                $classes = 'dashboard-nav-link';
                if ($item['active']) {
                    $classes .= ' active';
                }

                echo '<a href="' . esc_url($item['url']) . '" class="' . esc_attr($classes) . '">';
                echo '<i class="' . esc_attr($item['icon']) . '"></i>';
                echo '<span>' . esc_html($item['label']) . '</span>';
                echo '</a>';
            }
            ?>
            <a href="<?php echo esc_url(wc_logout_url()); ?>" class="dashboard-nav-link logout">
                <i class="fas fa-sign-out-alt"></i>
                <span><?php esc_html_e('Déconnexion', 'chassesautresor'); ?></span>
            </a>
        </nav>
        <?php if (current_user_can('administrator')) : ?>
        <nav class="dashboard-nav admin-nav">
            <span class="dashboard-nav-heading"><?php esc_html_e('Administration', 'chassesautresor'); ?></span>
            <?php
            $admin_items = array(
                array(
                    'label'   => __('Organisateurs', 'chassesautresor'),
                    'icon'    => 'fas fa-users',
                    'url'     => home_url('/mon-compte/organisateurs/'),
                    'section' => 'organisateurs',
                    'active'  => $current_path === 'mon-compte/organisateurs',
                ),
                array(
                    'label'   => __('Statistiques', 'chassesautresor'),
                    'icon'    => 'fas fa-chart-line',
                    'url'     => home_url('/mon-compte/statistiques/'),
                    'section' => 'statistiques',
                    'active'  => $current_path === 'mon-compte/statistiques',
                ),
                array(
                    'label'   => __('Outils', 'chassesautresor'),
                    'icon'    => 'fas fa-wrench',
                    'url'     => home_url('/mon-compte/outils/'),
                    'section' => 'outils',
                    'active'  => $current_path === 'mon-compte/outils',
                ),
            );

            foreach ($admin_items as $item) {
                $classes = 'dashboard-nav-link';
                if ($item['active']) {
                    $classes .= ' active';
                }

                echo '<a href="' . esc_url($item['url']) . '" data-section="' . esc_attr($item['section']) . '" class="' . esc_attr($classes) . '">';
                echo '<i class="' . esc_attr($item['icon']) . '"></i>';
                echo '<span>' . esc_html($item['label']) . '</span>';
                echo '</a>';
            }
            ?>
        </nav>
        <?php endif; ?>
        <?php
        $organizer_id = get_organisateur_from_user($current_user->ID);
        if ($organizer_id) {
            $chasses = get_posts([
                'post_type'   => 'chasse',
                'post_status' => ['publish', 'pending'],
                'numberposts' => -1,
                'meta_query'  => [
                    'relation' => 'AND',
                    [
                        'key'     => 'chasse_cache_organisateur',
                        'value'   => '"' . $organizer_id . '"',
                        'compare' => 'LIKE',
                    ],
                    [
                        'key'     => 'chasse_cache_statut_validation',
                        'value'   => 'banni',
                        'compare' => '!=',
                    ],
                ],
            ]);
            ?>
            <nav class="dashboard-nav organizer-nav">
                <a href="<?php echo esc_url(get_permalink($organizer_id)); ?>" class="dashboard-nav-link">
                    <i class="fas fa-landmark"></i>
                    <span><?php echo esc_html(get_the_title($organizer_id)); ?></span>
                </a>
                <?php foreach ($chasses as $chasse) :
                    $status = get_field('chasse_cache_statut_validation', $chasse->ID);
                    $label  = $status ? ' (' . esc_html(ucfirst(str_replace('_', ' ', $status))) . ')' : '';
                    ?>
                    <a href="<?php echo esc_url(get_permalink($chasse->ID)); ?>" class="dashboard-nav-sublink">
                        <?php echo esc_html(get_the_title($chasse->ID)) . $label; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php }
        ?>
        <?php endif; ?>
    </aside>
    <div class="myaccount-main">
        <header class="myaccount-header">
            <!-- TODO: header content -->
        </header>
        <main class="myaccount-content">
            <section class="msg-important"></section>
            <?php
            if ($content_template && file_exists($content_template)) {
                include $content_template;
            } else {
                if (function_exists('woocommerce_account_content')) {
                    woocommerce_account_content();
                } else {
                    echo '<p>' . esc_html__('Content not found.', 'chassesautresor') . '</p>';
                }
            }
            ?>
        </main>
    </div>
</div>

