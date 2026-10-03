<?php

defined('ABSPATH') || exit;

use ChassesAuTresor\Core\Presentation\PublicViewModelFactory;

$viewModel = (new PublicViewModelFactory())->forPost((int) get_queried_object_id());
get_header();
?>
<main class="cat-core-view cat-core-hunt">
    <article>
        <header class="cat-core-header">
            <h1><?php echo esc_html($viewModel['title']); ?></h1>
            <?php if ($viewModel['image'] !== '') : ?>
                <img src="<?php echo esc_url($viewModel['image']); ?>" alt="">
            <?php endif; ?>
        </header>
        <div class="cat-core-content"><?php echo wp_kses_post($viewModel['content']); ?></div>
        <?php if (!empty($viewModel['organizer_id'])) : ?>
            <p>
                <?php esc_html_e('Organisée par', 'chassesautresor-com'); ?>
                <a href="<?php echo esc_url(get_permalink($viewModel['organizer_id'])); ?>">
                    <?php echo esc_html(get_the_title($viewModel['organizer_id'])); ?>
                </a>
            </p>
        <?php endif; ?>
        <section aria-labelledby="cat-core-riddles-title">
            <h2 id="cat-core-riddles-title"><?php esc_html_e('Énigmes', 'chassesautresor-com'); ?></h2>
            <?php if ($viewModel['riddles'] === []) : ?>
                <p><?php esc_html_e('Aucune énigme n’est disponible pour le moment.', 'chassesautresor-com'); ?></p>
            <?php else : ?>
                <ul class="cat-core-cards">
                    <?php foreach ($viewModel['riddles'] as $riddle) : ?>
                        <li>
                            <a href="<?php echo esc_url($riddle['url']); ?>">
                                <?php echo esc_html($riddle['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php if ($viewModel['can_edit']) : ?>
            <p><a class="cat-core-button" href="<?php echo esc_url(get_edit_post_link($viewModel['id'])); ?>">
                <?php esc_html_e('Modifier cette chasse', 'chassesautresor-com'); ?>
            </a></p>
        <?php endif; ?>
    </article>
</main>
<?php get_footer(); ?>
