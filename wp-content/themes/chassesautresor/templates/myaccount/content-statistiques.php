<?php
/**
 * Dynamic content for the "Statistiques" section.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (!current_user_can('administrator')) {
    wp_redirect(home_url('/mon-compte/'));
    exit;
}

$user_id = get_current_user_id();
$wins = compter_chasses_gagnees($user_id);
?>
<section>
    <h1 class="mb-4 text-xl font-semibold"><?php esc_html_e('Statistiques', 'chassesautresor'); ?></h1>
    <p><?php echo esc_html(sprintf(__('Chasses gagnées : %d', 'chassesautresor'), $wins)); ?></p>
</section>
