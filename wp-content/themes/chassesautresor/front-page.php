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
                <div class="titre-chasses-wrapper">
                    <div class="titre-chasses">
                        <span class="decor decor-gauche">
                            <?php echo file_get_contents(get_theme_file_path('assets/svg/star-line-right.svg')); ?>
                        </span>
                        <h2><?php esc_html_e('Chasses au trésor', 'chassesautresor-com'); ?></h2>
                        <span class="decor decor-droite">
                            <?php echo file_get_contents(get_theme_file_path('assets/svg/star-line-left.svg')); ?>
                        </span>
                    </div>
                </div>
                <div class="ligne-chasses"></div>
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
