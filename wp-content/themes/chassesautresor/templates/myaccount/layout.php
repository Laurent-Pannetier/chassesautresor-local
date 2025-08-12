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
                    'label'    => __('Commandes', 'chassesautresor'),
                    'icon'     => 'fas fa-box',
                    'url'      => wc_get_account_endpoint_url('orders'),
                    'active'   => is_wc_endpoint_url('orders'),
                ),
                array(
                    'endpoint' => 'edit-address',
                    'label'    => __('Adresses', 'chassesautresor'),
                    'icon'     => 'fas fa-map-marker-alt',
                    'url'      => wc_get_account_endpoint_url('edit-address'),
                    'active'   => is_wc_endpoint_url('edit-address'),
                ),
                array(
                    'endpoint' => 'edit-account',
                    'label'    => __('Paramètres', 'chassesautresor'),
                    'icon'     => 'fas fa-cog',
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
            <a href="<?php echo esc_url(wc_logout_url()); ?>" class="dashboard-nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span><?php esc_html_e('Déconnexion', 'chassesautresor'); ?></span>
            </a>
        </nav>
        <?php endif; ?>
    </aside>
    <div class="myaccount-main">
        <header class="myaccount-header">
            <!-- TODO: header content -->
        </header>
        <main class="myaccount-content">
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

