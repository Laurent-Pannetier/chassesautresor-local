<?php

defined('ABSPATH') || exit;

use ChassesAuTresor\Core\Presentation\PublicArchiveViewModelFactory;

$viewModel = (new PublicArchiveViewModelFactory())->current();
get_header();
?>
<main class="cat-core-view cat-core-archive">
    <header><h1><?php echo esc_html($viewModel['title']); ?></h1></header>
    <?php if ($viewModel['items'] === []) : ?>
        <p><?php esc_html_e('Aucun contenu disponible pour le moment.', 'chassesautresor-com'); ?></p>
    <?php else : ?>
        <ul class="cat-core-cards">
            <?php foreach ($viewModel['items'] as $item) : ?>
                <li>
                    <article>
                        <?php if ($item['image'] !== '') : ?>
                            <a href="<?php echo esc_url($item['url']); ?>" tabindex="-1" aria-hidden="true">
                                <img src="<?php echo esc_url($item['image']); ?>" alt="">
                            </a>
                        <?php endif; ?>
                        <h2>
                            <a href="<?php echo esc_url($item['url']); ?>">
                                <?php echo esc_html($item['title']); ?>
                            </a>
                        </h2>
                        <p><?php echo esc_html($item['excerpt']); ?></p>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php echo wp_kses_post($viewModel['pagination']); ?>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
