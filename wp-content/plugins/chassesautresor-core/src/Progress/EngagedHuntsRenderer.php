<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Render an autonomous fallback for the current user's engaged hunts. */
final class EngagedHuntsRenderer {
    /** @param array{ids: int[], page: int, total_pages: int, total_items: int} $pagination */
    public function render(array $pagination): string {
        $huntIds = array_values(array_filter(array_map('intval', $pagination['ids'] ?? [])));
        if ($huntIds === []) {
            return '<p class="myaccount-placeholder">'
                . esc_html__('Vous ne participez à aucune chasse pour le moment.', 'chassesautresor-com')
                . '</p>';
        }

        ob_start();
        ?>
        <div class="cards-grid myaccount-chasses-engagees-grid">
            <?php foreach ($huntIds as $huntId) : ?>
                <?= $this->card($huntId); ?>
            <?php endforeach; ?>
        </div>
        <?php
        if ($pagination['total_pages'] > 1) {
            echo \cta_render_pager(
                $pagination['page'],
                $pagination['total_pages'],
                'engaged-hunts-pager',
                ['data-param' => \ca_get_engaged_hunts_page_param()]
            );
        }

        return trim((string) ob_get_clean());
    }

    private function card(int $huntId): string {
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
            ['class' => 'engaged-hunt-card__image', 'loading' => 'lazy']
        );

        ob_start();
        ?>
        <article class="carte carte-chasse engaged-hunt-card">
            <?php if ($image !== '') : ?>
                <a href="<?= esc_url($permalink); ?>" aria-hidden="true" tabindex="-1"><?= $image; ?></a>
            <?php endif; ?>
            <div class="engaged-hunt-card__content">
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
