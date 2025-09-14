<?php
/**
 * Homepage displaying all valid hunts.
 */

defined('ABSPATH') || exit;

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
    </main>
</div>

<?php get_footer(); ?>
