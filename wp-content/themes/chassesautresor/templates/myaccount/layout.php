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

get_header();
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
                    'icon'     => 'fas fa-shopping-cart',
                    'url'      => wc_get_account_endpoint_url('orders'),
                    'active'   => is_wc_endpoint_url('orders'),
                ),
                array(
                    'endpoint' => 'chasses',
                    'label'    => __('Chasses', 'chassesautresor-com'),
                    'icon'     => 'fas fa-map',
                    'url'      => home_url('/mon-compte/?section=chasses'),
                    'section'  => 'chasses',
                    'active'   => isset($_GET['section']) && $_GET['section'] === 'chasses',
                ),
                array(
                    'endpoint' => 'points',
                    'label'    => __('Points', 'chassesautresor'),
                    'icon'     => 'fas fa-coins',
                    'url'      => home_url('/mon-compte/?section=points'),
                    'section'  => 'points',
                    'active'   => isset($_GET['section']) && $_GET['section'] === 'points',
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

                $data_attr = isset($item['section']) ? ' data-section="' . esc_attr($item['section']) . '"' : '';
                echo '<a href="' . esc_url($item['url']) . '"' . $data_attr . ' class="' . esc_attr($classes) . '">';
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

                echo '<a href="' . esc_url($item['url']) . '" data-section="' .
                    esc_attr($item['section']) . '" class="' . esc_attr($classes) . '">';
                echo '<i class="' . esc_attr($item['icon']) . '"></i>';
                echo '<span>' . esc_html($item['label']) . '</span>';
                echo '</a>';
            }
            ?>
        </nav>
        <?php endif; ?>
        <?php
        $organizer_nav = myaccount_get_organizer_nav($current_user->ID);
        if ($organizer_nav) {
            echo myaccount_render_organizer_nav($organizer_nav);
        }
        ?>
        <?php endif; ?>
    </aside>
    <div class="myaccount-main">
        <header class="myaccount-header">
            <!-- TODO: header content -->
        </header>
        <main class="myaccount-content">
            <?php
            $messages = '';
            if (empty($_GET['section'])) {
                $messages = trim(myaccount_get_important_messages());
            }
            ?>
            <section class="msg-important"><?php echo $messages; ?></section>
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

<?php
get_footer();

