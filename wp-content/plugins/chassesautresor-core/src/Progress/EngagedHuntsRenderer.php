<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\HuntCardRenderer;

/** Render an autonomous fallback for the current user's engaged hunts. */
final class EngagedHuntsRenderer {
    private EngagedHuntsRecommendationService $recommendations;
    private HuntCardRenderer $cards;

    public function __construct(
        ?EngagedHuntsRecommendationService $recommendations = null,
        ?HuntCardRenderer $cards = null
    ) {
        $this->recommendations = $recommendations ?? new EngagedHuntsRecommendationService();
        $this->cards = $cards ?? new HuntCardRenderer();
    }

    /** @param array{ids: int[], page: int, total_pages: int, total_items: int} $pagination */
    public function render(array $pagination): string {
        $huntIds = array_values(array_filter(array_map('intval', $pagination['ids'] ?? [])));
        if ($huntIds === []) {
            return $this->emptyState();
        }

        $html = $this->cards->grid($huntIds, 'cards-grid myaccount-chasses-engagees-grid');
        if ($pagination['total_pages'] > 1) {
            $html .= \cta_render_pager(
                $pagination['page'],
                $pagination['total_pages'],
                'engaged-hunts-pager',
                ['data-param' => \ca_get_engaged_hunts_page_param()]
            );
        }

        return $html;
    }

    private function emptyState(): string {
        $recommendedIds = $this->recommendations->find();
        $catalogUrl = apply_filters('ca_recommended_hunts_catalog_url', home_url('/'), '/');
        ob_start();
        ?>
        <div class="myaccount-recommended-hunts">
            <p class="myaccount-placeholder">
                <?= esc_html__(
                    'Vous ne participez à aucune chasse pour le moment. Voici quelques idées pour démarrer.',
                    'chassesautresor-com'
                ); ?>
            </p>
            <?php if ($recommendedIds !== []) : ?>
                <h3><?= esc_html__('Chasses recommandées', 'chassesautresor-com'); ?></h3>
                <?= $this->cards->grid($recommendedIds, 'cards-grid myaccount-recommended-hunts-grid'); ?>
            <?php else : ?>
                <p><?= esc_html__(
                    'Aucune recommandation disponible pour le moment, mais notre catalogue vous attend.',
                    'chassesautresor-com'
                ); ?></p>
            <?php endif; ?>
            <a class="bouton-cta myaccount-recommended-hunts-cta" href="<?= esc_url($catalogUrl); ?>">
                <?= esc_html__('Explorer toutes nos chasses', 'chassesautresor-com'); ?>
            </a>
        </div>
        <?php
        return trim((string) ob_get_clean());
    }

}
