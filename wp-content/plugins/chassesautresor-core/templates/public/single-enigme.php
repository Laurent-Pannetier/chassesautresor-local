<?php

defined('ABSPATH') || exit;

use ChassesAuTresor\Core\Presentation\PublicViewModelFactory;

$viewModel = (new PublicViewModelFactory())->forPost((int) get_queried_object_id());
get_header();
?>
<main class="cat-core-view cat-core-riddle">
    <article>
        <?php if (!$viewModel['visible']) : ?>
            <p role="alert"><?php esc_html_e('Cette énigme n’est pas encore accessible.', 'chassesautresor-com'); ?></p>
        <?php else : ?>
            <header class="cat-core-header">
                <h1><?php echo esc_html($viewModel['title']); ?></h1>
                <?php if ($viewModel['image'] !== '') : ?>
                    <img src="<?php echo esc_url($viewModel['image']); ?>" alt="">
                <?php endif; ?>
            </header>
            <div class="cat-core-content"><?php echo wp_kses_post($viewModel['content']); ?></div>
            <?php if (is_user_logged_in()) : ?>
                <section class="cat-core-answer" aria-labelledby="cat-core-answer-title">
                    <h2 id="cat-core-answer-title"><?php esc_html_e('Votre réponse', 'chassesautresor-com'); ?></h2>
                    <?php echo do_shortcode('[formulaire_reponse_manuelle id="' . (int) $viewModel['id'] . '"]'); ?>
                    <div class="cat-core-feedback" role="status" aria-live="polite"></div>
                </section>
            <?php else : ?>
                <p><a class="cat-core-button" href="<?php echo esc_url(wp_login_url($viewModel['permalink'])); ?>">
                    <?php esc_html_e('Se connecter pour participer', 'chassesautresor-com'); ?>
                </a></p>
            <?php endif; ?>
            <?php if (!empty($viewModel['hunt_id'])) : ?>
                <p><a href="<?php echo esc_url(get_permalink($viewModel['hunt_id'])); ?>">
                    <?php esc_html_e('Retour à la chasse', 'chassesautresor-com'); ?>
                </a></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($viewModel['can_edit']) : ?>
            <p><a href="<?php echo esc_url(get_edit_post_link($viewModel['id'])); ?>">
                <?php esc_html_e('Modifier cette énigme', 'chassesautresor-com'); ?>
            </a></p>
        <?php endif; ?>
    </article>
</main>
<?php get_footer(); ?>
