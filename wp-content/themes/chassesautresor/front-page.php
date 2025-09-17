<?php
/**
 * Homepage displaying all valid hunts.
 */

defined('ABSPATH') || exit;

$points_history = '';
if (is_user_logged_in() && function_exists('render_points_history_table')) {
    $points_history = render_points_history_table((int) get_current_user_id());
}

get_header();

$query = new WP_Query([
    'post_type'      => 'chasse',
    'post_status'    => 'publish',
    'meta_query'     => [
        [
            'key'   => 'chasse_cache_statut_validation',
            'value' => 'valide',
        ],
    ],
    'fields'         => 'ids',
    'posts_per_page' => -1,
]);

$chasse_ids = $query->posts;
?>

<div id="primary" class="content-area">
    <main id="home-page">
        <section class="chasses">
            <div class="conteneur">
                <div class="liste-chasses">
                    <?php
                    get_template_part('template-parts/organisateur/organisateur-partial-boucle-chasses', null, [
                        'chasse_ids' => $chasse_ids,
                        'show_header' => false,
                        'grid_class' => 'organisateur-chasses-grid',
                    ]);
                    ?>
                </div>
            </div>
        </section>
        <?php if ($points_history) : ?>
            <section class="points-history">
                <div class="conteneur">
                    <?php echo $points_history; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>
