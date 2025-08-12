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
<div class="grid min-h-screen w-full lg:grid-cols-[280px_1fr] bg-[hsl(var(--background))] text-[hsl(var(--foreground))]">
    <aside class="hidden border-r border-[hsl(var(--border))] bg-[hsl(var(--background))] lg:block">
        <div class="flex h-full flex-col">
            <div class="flex h-14 items-center border-b border-[hsl(var(--border))] px-4">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-2 font-semibold">
                    <?php echo esc_html($display_name); ?>
                </a>
            </div>
            <div class="flex-1 overflow-y-auto">
                <?php if ($show_nav) : ?>
                <nav class="dashboard-nav grid items-start gap-2 px-2 py-4 text-sm font-medium lg:px-4">
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
                        $classes  = 'flex items-center gap-3 rounded-lg px-3 py-2 transition-all ';
                        $classes .= 'hover:text-[hsl(var(--primary))] ';
                        $classes .= $item['active']
                            ? 'bg-[hsl(var(--muted))] text-[hsl(var(--primary))]'
                            : 'text-[hsl(var(--muted-foreground))]';

                        echo '<a href="' . esc_url($item['url']) . '" class="' . esc_attr($classes) . '">';
                        echo '<i class="' . esc_attr($item['icon']) . '"></i>';
                        echo '<span>' . esc_html($item['label']) . '</span>';
                        echo '</a>';
                    }
                    ?>
                    <a href="<?php echo esc_url(wc_logout_url()); ?>"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-[hsl(var(--muted-foreground))] transition-all hover:text-[hsl(var(--primary))]">
                        <i class="fas fa-sign-out-alt"></i>
                        <span><?php esc_html_e('Déconnexion', 'chassesautresor'); ?></span>
                    </a>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </aside>
    <div class="flex flex-col">
        <header class="flex h-14 items-center gap-4 border-b border-[hsl(var(--border))] bg-[hsl(var(--background))] px-4">
            <!-- TODO: header content -->
        </header>
        <main class="flex-1 overflow-y-auto p-4">
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
