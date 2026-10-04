<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use ChassesAuTresor\Core\Progress\RiddleStatisticsApplicationService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;
use Closure;

/** Render per-riddle statistics for organizer/admin account home. */
final class AccountHuntRiddleStatisticsRenderer
{
    private Closure $rowsProvider;

    public function __construct(?callable $rowsProvider = null)
    {
        $this->rowsProvider = Closure::fromCallable(
            $rowsProvider ?? [$this, 'defaultRows']
        );
    }

    public function render(int $huntId): string
    {
        if ($huntId <= 0) {
            return $this->emptyCard(
                __('Statistiques des énigmes', 'chassesautresor-com'),
                __('Aucune chasse gérée n’est disponible pour afficher les statistiques.', 'chassesautresor-com')
            );
        }

        $rows = (array) ($this->rowsProvider)($huntId);
        if ($rows === []) {
            return $this->emptyCard(
                __('Statistiques des énigmes', 'chassesautresor-com'),
                __('Aucune énigme associée à cette chasse pour le moment.', 'chassesautresor-com')
            );
        }

        ob_start();
        ?>
        <div class="dashboard-card myaccount-riddle-stats">
            <div class="dashboard-card-header">
                <i class="fas fa-chart-bar" aria-hidden="true"></i>
                <h3><?php esc_html_e('Statistiques des énigmes', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="dashboard-card-content">
                <ul class="myaccount-riddle-stats-list">
                    <?php foreach ($rows as $row) : ?>
                        <li class="myaccount-riddle-stats-item">
                            <h4 class="myaccount-riddle-stats-item__title">
                                <?php echo esc_html((string) ($row['title'] ?? '')); ?>
                            </h4>
                            <dl class="myaccount-riddle-stats-metrics">
                                <?php
                                echo $this->metric(
                                    __('Participants', 'chassesautresor-com'),
                                    (string) (int) ($row['participants'] ?? 0)
                                );
                                echo $this->metric(
                                    __('Trouvées', 'chassesautresor-com'),
                                    (string) (int) ($row['trouves'] ?? 0)
                                );
                                echo $this->metric(
                                    __('Tentatives', 'chassesautresor-com'),
                                    (string) (int) ($row['tentatives'] ?? 0)
                                );
                                echo $this->metric(
                                    __('Étapes', 'chassesautresor-com'),
                                    $this->stepsLabel($row)
                                );
                                ?>
                            </dl>
                            <div class="myaccount-riddle-stats-ranking">
                                <span class="myaccount-riddle-stats-ranking__label">
                                    <?php esc_html_e('Classement', 'chassesautresor-com'); ?>
                                </span>
                                <span class="myaccount-riddle-stats-ranking__value">
                                    <?php echo esc_html($this->rankingLabel((array) ($row['ranking'] ?? []))); ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php

        return trim((string) ob_get_clean());
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultRows(int $huntId): array
    {
        global $wpdb;

        $application = new RiddleStatisticsApplicationService(
            CoreServiceFactory::riddleStatistics($wpdb)
        );

        return $application->overviewForHunt($huntId);
    }

    private function metric(string $label, string $value): string
    {
        return '<div class="myaccount-riddle-stats-metric">'
            . '<dt>' . esc_html($label) . '</dt>'
            . '<dd>' . esc_html($value) . '</dd>'
            . '</div>';
    }

    /** @param array<string, mixed> $row */
    private function stepsLabel(array $row): string
    {
        $steps = (int) ($row['steps'] ?? 0);
        if ($steps <= 0) {
            return '—';
        }

        $players = (int) ($row['step_players'] ?? 0);
        $playersLabel = sprintf(
            /* translators: %d: number of players who completed at least one intermediate step */
            _n('%d joueur', '%d joueurs', $players, 'chassesautresor-com'),
            $players
        );

        return sprintf(
            /* translators: 1: number of intermediate steps, 2: localized player count label */
            __('%1$d (%2$s)', 'chassesautresor-com'),
            $steps,
            $playersLabel
        );
    }

    /** @param array<int, array<string, mixed>> $ranking */
    private function rankingLabel(array $ranking): string
    {
        if ($ranking === []) {
            return '—';
        }

        $parts = [];
        foreach (array_values($ranking) as $index => $solver) {
            $name = trim((string) ($solver['username'] ?? ''));
            if ($name === '') {
                continue;
            }
            $parts[] = ($index + 1) . '. ' . $name;
        }

        return $parts === [] ? '—' : implode(' · ', $parts);
    }

    private function emptyCard(string $title, string $message): string
    {
        return '<div class="dashboard-card myaccount-riddle-stats">'
            . '<div class="dashboard-card-header">'
            . '<i class="fas fa-chart-bar" aria-hidden="true"></i>'
            . '<h3>' . esc_html($title) . '</h3>'
            . '</div>'
            . '<div class="dashboard-card-content"><p>' . esc_html($message) . '</p></div>'
            . '</div>';
    }
}
