<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Render portable hunt cards without theme template parts. */
final class HuntCardRenderer {
    /** @param int[] $huntIds */
    public function grid(array $huntIds, string $class = 'cards-grid'): string {
        $huntIds = array_values(array_filter(array_map('intval', $huntIds)));
        ob_start();
        ?>
        <div class="<?= esc_attr($class); ?>">
            <?php foreach ($huntIds as $huntId) : ?>
                <?= $this->card($huntId); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return trim((string) ob_get_clean());
    }

    public function card(int $huntId): string {
        $title = get_the_title($huntId);
        $permalink = get_permalink($huntId);
        $excerpt = (string) get_post_field('post_excerpt', $huntId);
        if ($excerpt === '') {
            $excerpt = get_post_field('post_content', $huntId);
        }
        $excerpt = wp_trim_words(wp_strip_all_tags((string) $excerpt), 30, '…');
        $image = get_the_post_thumbnail(
            $huntId,
            'medium_large',
            ['class' => 'hunt-card__image', 'loading' => 'lazy']
        );

        ob_start();
        ?>
        <article class="carte carte-chasse hunt-card">
            <?php if ($image !== '') : ?>
                <a href="<?= esc_url($permalink); ?>" aria-hidden="true" tabindex="-1">
                    <?= wp_kses_post($image); ?>
                </a>
            <?php endif; ?>
            <div class="hunt-card__content">
                <h3><a href="<?= esc_url($permalink); ?>"><?= esc_html($title); ?></a></h3>
                <?php if ($excerpt !== '') : ?>
                    <p><?= esc_html($excerpt); ?></p>
                <?php endif; ?>
                <a class="bouton-secondaire" href="<?= esc_url($permalink); ?>">
                    <?= esc_html__('En savoir plus', 'chassesautresor-com'); ?>
                </a>
            </div>
        </article>
        <?php
        return trim((string) ob_get_clean());
    }
}
