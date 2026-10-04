<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use Closure;

/** Render the global statistics section of the administrator account area. */
final class AccountStatisticsRenderer {
    private Closure $pointsFactory;
    private Closure $winsCounter;

    public function __construct(?callable $pointsFactory = null, ?callable $winsCounter = null) {
        $this->pointsFactory = Closure::fromCallable(
            $pointsFactory ?? static fn () => \cat_get_points_service()
        );
        $this->winsCounter = Closure::fromCallable(
            $winsCounter ?? static fn (int $userId): int => \compter_chasses_gagnees($userId)
        );
    }

    public function render(int $userId): string {
        $wins = (int) ($this->winsCounter)($userId);
        $pointsUiEnabled = !function_exists('cat_is_points_ui_enabled') || cat_is_points_ui_enabled();

        ob_start();
        ?>
        <section>
            <h1 class="mb-4 text-xl font-semibold">
                <?= esc_html__('Statistiques', 'chassesautresor-com'); ?>
            </h1>
            <?php if ($pointsUiEnabled) : ?>
                <?php
                $points = ($this->pointsFactory)();
                $usedPoints = $points->getTotalUsed();
                $circulationPoints = $points->getTotalInCirculation();
                ?>
                <div class="dashboard-grid stats-cards myaccount-points-cards">
                    <?= $this->card(
                        'points-used',
                        'fa-hand-holding-dollar',
                        __('Points utilisés', 'chassesautresor-com'),
                        $usedPoints
                    ); ?>
                    <?= $this->card(
                        'points-bought',
                        'fa-cart-shopping',
                        __('Points achetés', 'chassesautresor-com'),
                        __('À implémenter', 'chassesautresor-com')
                    ); ?>
                    <?= $this->card(
                        'points-circulation',
                        'fa-arrows-rotate',
                        __('Points en circulation', 'chassesautresor-com'),
                        $circulationPoints
                    ); ?>
                </div>
            <?php endif; ?>
            <p><?= esc_html(sprintf(__('Chasses gagnées : %d', 'chassesautresor-com'), $wins)); ?></p>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }

    /** @param int|string $value */
    private function card(string $stat, string $icon, string $label, $value): string {
        return '<div class="dashboard-card" data-stat="' . esc_attr($stat) . '">'
            . '<i class="fa-solid ' . esc_attr($icon) . '" aria-hidden="true"></i>'
            . '<h3>' . esc_html($label) . '</h3>'
            . '<p class="stat-value">' . esc_html((string) $value) . '</p></div>';
    }
}
