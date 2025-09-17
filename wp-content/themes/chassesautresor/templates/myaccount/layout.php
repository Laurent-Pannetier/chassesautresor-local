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
$current_path     = '';
if (!empty($_SERVER['REQUEST_URI'])) {
    $current_path = trim(parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/');
}

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
                    'label'    => __('Accueil', 'chassesautresor-com'),
                    'icon'     => 'fas fa-home',
                    'url'      => wc_get_account_endpoint_url('dashboard'),
                    'active'   => is_account_page() && !is_wc_endpoint_url(),
                ),
                array(
                    'endpoint' => 'orders',
                    'label'    => __('Commandes', 'chassesautresor-com'),
                    'icon'     => 'fas fa-shopping-cart',
                    'url'      => wc_get_account_endpoint_url('orders'),
                    'active'   => is_wc_endpoint_url('orders'),
                ),
                array(
                    'endpoint' => 'chasses',
                    'label'    => __('Chasses', 'chassesautresor-com'),
                    'title'    => __('Vos chasses', 'chassesautresor-com'),
                    'icon'     => 'fas fa-map',
                    'url'      => home_url('/mon-compte/?section=chasses'),
                    'section'  => 'chasses',
                    'active'   => isset($_GET['section']) && $_GET['section'] === 'chasses',
                ),
                array(
                    'endpoint' => 'points',
                    'label'    => __('Points', 'chassesautresor-com'),
                    'title'    => __('Points', 'chassesautresor-com'),
                    'icon'     => 'fas fa-coins',
                    'url'      => home_url('/mon-compte/?section=points'),
                    'section'  => 'points',
                    'active'   => isset($_GET['section']) && $_GET['section'] === 'points',
                ),
                array(
                    'endpoint' => 'edit-account',
                    'label'    => __('Profil', 'chassesautresor-com'),
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

                $data_attr = '';
                if (isset($item['section'])) {
                    $data_attr .= ' data-section="' . esc_attr($item['section']) . '"';
                }
                if (isset($item['title'])) {
                    $data_attr .= ' data-title="' . esc_attr($item['title']) . '"';
                }

                echo '<a href="' . esc_url($item['url']) . '"' . $data_attr . ' class="' . esc_attr($classes) . '">';
                echo '<i class="' . esc_attr($item['icon']) . '"></i>';
                echo '<span>' . esc_html($item['label']) . '</span>';
                echo '</a>';
            }
            ?>
            <a href="<?php echo esc_url(wc_logout_url()); ?>" class="dashboard-nav-link logout">
                <i class="fas fa-sign-out-alt"></i>
                <span><?php esc_html_e('Déconnexion', 'chassesautresor-com'); ?></span>
            </a>
        </nav>
        <?php if (current_user_can('administrator')) : ?>
        <nav class="dashboard-nav admin-nav">
            <span class="dashboard-nav-heading"><?php esc_html_e('Administration', 'chassesautresor-com'); ?></span>
            <?php
            $admin_items = array(
                array(
                    'label'   => __('Organisateurs', 'chassesautresor-com'),
                    'icon'    => 'fas fa-users',
                    'url'     => home_url('/mon-compte/organisateurs/'),
                    'section' => 'organisateurs',
                    'active'  => $current_path === 'mon-compte/organisateurs',
                ),
                array(
                    'label'   => __('Statistiques', 'chassesautresor-com'),
                    'icon'    => 'fas fa-chart-line',
                    'url'     => home_url('/mon-compte/statistiques/'),
                    'section' => 'statistiques',
                    'active'  => $current_path === 'mon-compte/statistiques',
                ),
                array(
                    'label'   => __('Outils', 'chassesautresor-com'),
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
        $organizer_roles = array();
        if (defined('ROLE_ORGANISATEUR')) {
            $organizer_roles[] = ROLE_ORGANISATEUR;
        } else {
            $organizer_roles[] = 'organisateur';
        }
        if (defined('ROLE_ORGANISATEUR_CREATION')) {
            $organizer_roles[] = ROLE_ORGANISATEUR_CREATION;
        } else {
            $organizer_roles[] = 'organisateur_creation';
        }

        if (array_intersect($organizer_roles, (array) $current_user->roles)) {
            $organizer_id = function_exists('get_organisateur_from_user')
                ? get_organisateur_from_user((int) $current_user->ID)
                : 0;

            if ($organizer_id && function_exists('get_the_title')) {
                $organisation_title = get_the_title($organizer_id);
                if ($organisation_title) {
                    $organisation_url     = home_url('/mon-compte/organisation/');
                    $organisation_classes = 'dashboard-nav-link';
                    if ($current_path === 'mon-compte/organisation') {
                        $organisation_classes .= ' active';
                    }
                    ?>
                    <nav class="dashboard-nav organizer-organisation-nav">
                        <a href="<?php echo esc_url($organisation_url); ?>" class="<?php echo esc_attr($organisation_classes); ?>">
                            <i class="fas fa-landmark"></i>
                            <span><?php echo esc_html($organisation_title); ?></span>
                        </a>
                    </nav>
                    <?php
                }
            }
        }
        ?>
        <?php endif; ?>
    </aside>
    <div class="myaccount-main">
        <header class="myaccount-header">
            <?php
            $page_title = '';
            if (is_wc_endpoint_url('edit-account')) {
                $page_title = __('Votre profil', 'chassesautresor-com');
            } elseif (is_wc_endpoint_url('orders')) {
                $page_title = __('Vos commandes', 'chassesautresor-com');
            } elseif (isset($_GET['section']) && $_GET['section'] === 'chasses') {
                $page_title = __('Vos chasses', 'chassesautresor-com');
            } elseif (isset($_GET['section']) && $_GET['section'] === 'points') {
                $page_title = __('Points', 'chassesautresor-com');
            } elseif ($current_path === 'mon-compte/organisation') {
                if (function_exists('get_organisateur_from_user')) {
                    $organizer_id = get_organisateur_from_user((int) $current_user->ID);
                    if ($organizer_id && function_exists('get_the_title')) {
                        $page_title = get_the_title($organizer_id);
                    }
                }
                if (empty($page_title)) {
                    $page_title = __('Mon organisation', 'chassesautresor-com');
                }
            } elseif (is_account_page() && empty($_GET['section'])) {
                $page_title = sprintf(__('Bienvenue %s', 'chassesautresor-com'), $display_name);
            }
            if ($page_title) :
                ?>
                <h1 class="myaccount-title"><?php echo esc_html($page_title); ?></h1>
                <?php
            endif;
            ?>
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
                    echo '<p>' . esc_html__('Content not found.', 'chassesautresor-com') . '</p>';
                }
            }
            ?>
        </main>
    </div>
</div>

<?php
get_footer();

