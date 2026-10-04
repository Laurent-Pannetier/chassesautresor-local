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
                <div class="stats-table-wrapper">
                    <table class="stats-table compact myaccount-riddle-stats-table">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Énigme', 'chassesautresor-com'); ?></th>
                                <th scope="col"><?php esc_html_e('Participants', 'chassesautresor-com'); ?></th>
                                <th scope="col"><?php esc_html_e('Trouvées', 'chassesautresor-com'); ?></th>
                                <th scope="col"><?php esc_html_e('Tentatives', 'chassesautresor-com'); ?></th>
                                <th scope="col"><?php esc_html_e('Étapes', 'chassesautresor-com'); ?></th>
                                <th scope="col"><?php esc_html_e('Classement', 'chassesautresor-com'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) : ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html((string) ($row['title'] ?? '')); ?></th>
                                    <td><?php echo esc_html((string) (int) ($row['participants'] ?? 0)); ?></td>
                                    <td><?php echo esc_html((string) (int) ($row['trouves'] ?? 0)); ?></td>
                                    <td><?php echo esc_html((string) (int) ($row['tentatives'] ?? 0)); ?></td>
                                    <td><?php echo esc_html($this->stepsLabel($row)); ?></td>
                                    <td><?php echo esc_html($this->rankingLabel((array) ($row['ranking'] ?? []))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
