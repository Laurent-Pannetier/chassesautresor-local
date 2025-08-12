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
?>
<div class="grid min-h-screen w-full lg:grid-cols-[280px_1fr] bg-[hsl(var(--background))] text-[hsl(var(--foreground))]">
    <aside class="hidden border-r border-[hsl(var(--border))] bg-[hsl(var(--background))] lg:block">
        <div class="flex h-full flex-col">
            <div class="flex h-14 items-center border-b border-[hsl(var(--border))] px-4">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-2 font-semibold">
                    <?php bloginfo('name'); ?>
                </a>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <!-- TODO: navigation items -->
            </nav>
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
