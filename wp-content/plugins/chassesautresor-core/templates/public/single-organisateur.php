<?php

defined('ABSPATH') || exit;

use ChassesAuTresor\Core\Presentation\PublicViewModelFactory;

$viewModel = (new PublicViewModelFactory())->forPost((int) get_queried_object_id());
get_header();
?>
<main class="cat-core-view cat-core-organizer">
    <article>
        <header class="cat-core-header">
            <h1><?php echo esc_html($viewModel['title']); ?></h1>
            <?php if ($viewModel['image'] !== '') : ?>
                <img src="<?php echo esc_url($viewModel['image']); ?>" alt="">
            <?php endif; ?>
        </header>
        <div class="cat-core-content"><?php echo wp_kses_post($viewModel['content']); ?></div>
        <section aria-labelledby="cat-core-hunts-title">
            <h2 id="cat-core-hunts-title"><?php esc_html_e('Chasses au trésor', 'chassesautresor-com'); ?></h2>
            <?php if ($viewModel['hunts'] === []) : ?>
                <p><?php esc_html_e('Aucune chasse publique pour le moment.', 'chassesautresor-com'); ?></p>
            <?php else : ?>
                <ul class="cat-core-cards">
                    <?php foreach ($viewModel['hunts'] as $hunt) : ?>
                        <li>
                            <a href="<?php echo esc_url($hunt['url']); ?>">
                                <?php echo esc_html($hunt['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php if ($viewModel['can_edit']) : ?>
            <p><a class="cat-core-button" href="<?php echo esc_url(get_edit_post_link($viewModel['id'])); ?>">
                <?php esc_html_e('Modifier ce profil', 'chassesautresor-com'); ?>
            </a></p>
        <?php endif; ?>
    </article>
</main>
<?php get_footer(); ?>
