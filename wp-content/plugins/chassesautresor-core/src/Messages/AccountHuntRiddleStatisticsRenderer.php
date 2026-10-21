<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use ChassesAuTresor\Core\Progress\RiddleStatisticsApplicationService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;
use Closure;

/** Render hunt and per-riddle statistics for the organizer progression page. */
final class AccountHuntRiddleStatisticsRenderer
{
    private Closure $rowsProvider;
    private ?Closure $dashboardProvider;

    public function __construct(?callable $rowsProvider = null, ?callable $dashboardProvider = null)
    {
        $this->rowsProvider = Closure::fromCallable(
            $rowsProvider ?? [$this, 'defaultRows']
        );
        $this->dashboardProvider = $dashboardProvider === null
            ? null
            : Closure::fromCallable($dashboardProvider);
    }

    public function render(int $huntId, string $period = 'total', string $pageUrl = ''): string
    {
        if ($huntId <= 0) {
            return $this->emptyCard(
                __('Statistiques des énigmes', 'chassesautresor-com'),
                __('Aucune chasse gérée n’est disponible pour afficher les statistiques.', 'chassesautresor-com')
            );
        }

        if (!in_array($period, ['jour', 'semaine', 'mois', 'total'], true)) {
            $period = 'total';
        }
        $summary = [];
        if ($this->dashboardProvider !== null) {
            $dashboard = (array) ($this->dashboardProvider)($huntId, $period);
            $rows = (array) ($dashboard['riddles'] ?? []);
            $summary = (array) ($dashboard['summary'] ?? []);
        } else {
            $rows = (array) ($this->rowsProvider)($huntId);
        }

        if ($rows === [] && $summary === [] && $this->dashboardProvider === null) {
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
                <?php
                if ($this->dashboardProvider !== null) {
                    echo $this->periodNav($period, $pageUrl);
                    echo $this->summaryMetrics($summary);
                    echo $this->periodNote();
                }
                if ($rows === []) {
                    echo '<p>' . esc_html(__(
                        'Aucune énigme associée à cette chasse pour le moment.',
                        'chassesautresor-com'
                    )) . '</p>';
                } else {
                    echo '<ul class="myaccount-riddle-stats-list">';
                    foreach ($rows as $row) {
                        if (is_array($row)) {
                            echo $this->riddleItem($row);
                        }
                    }
                    echo '</ul>';
                }
                ?>
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

    private function periodNote(): string
    {
        return '<p class="progression-note">' . esc_html(__(
            'Les taux et les tentatives suivent la période. L’entonnoir décrit l’état actuel.',
            'chassesautresor-com'
        )) . '</p>';
    }

    /** @param array<string, mixed> $summary */
    private function summaryMetrics(array $summary): string
    {
        $winners = (int) ($summary['winners'] ?? 0);
        $cap = (int) ($summary['winner_cap'] ?? 0);
        $winnerValue = $cap > 0 ? $winners . '/' . $cap : (string) $winners;

        return '<dl class="myaccount-riddle-stats-metrics progression-hunt-metrics">'
            . $this->metric(__('Participants', 'chassesautresor-com'), (string) (int) ($summary['participants'] ?? 0))
            . $this->metric(
                __('Engagement', 'chassesautresor-com'),
                (int) ($summary['engagement_rate'] ?? 0) . ' %'
            )
            . $this->metric(
                __('Résolution', 'chassesautresor-com'),
                (int) ($summary['resolution_rate'] ?? 0) . ' %'
            )
            . $this->metric(__('Tentatives', 'chassesautresor-com'), (string) (int) ($summary['tentatives'] ?? 0))
            . $this->metric(__('Gagnants', 'chassesautresor-com'), $winnerValue)
            . '</dl>';
    }

    private function periodNav(string $period, string $pageUrl): string
    {
        $periods = [
            'jour' => __('Aujourd’hui', 'chassesautresor-com'),
            'semaine' => __('Cette semaine', 'chassesautresor-com'),
            'mois' => __('Ce mois', 'chassesautresor-com'),
            'total' => __('Total', 'chassesautresor-com'),
        ];
        $html = '<nav class="progression-period" aria-label="'
            . esc_attr(__('Période', 'chassesautresor-com')) . '">';
        foreach ($periods as $key => $label) {
            $current = $key === $period ? ' aria-current="page"' : '';
            $html .= '<a href="' . esc_url($this->periodUrl($pageUrl, $key)) . '"' . $current . '>'
                . esc_html($label) . '</a>';
        }

        return $html . '</nav>';
    }

    private function periodUrl(string $pageUrl, string $period): string
    {
        if (function_exists('add_query_arg')) {
            $base = $pageUrl !== '' ? $pageUrl : false;

            return (string) add_query_arg('periode', $period, $base);
        }

        if ($pageUrl === '') {
            return '?periode=' . rawurlencode($period);
        }

        $separator = str_contains($pageUrl, '?') ? '&' : '?';

        return $pageUrl . $separator . 'periode=' . rawurlencode($period);
    }

    /** @param array<string, mixed> $row */
    private function riddleItem(array $row): string
    {
        $html = '<li class="myaccount-riddle-stats-item">';
        $html .= '<h4 class="myaccount-riddle-stats-item__title">'
            . esc_html((string) ($row['title'] ?? '')) . '</h4>';
        if (is_array($row['funnel'] ?? null)) {
            $html .= $this->funnel((array) $row['funnel']);
        }
        $html .= '<dl class="myaccount-riddle-stats-metrics">';
        $html .= $this->metric(
            __('Participants', 'chassesautresor-com'),
            (string) (int) ($row['participants'] ?? 0)
        );
        $html .= $this->metric(
            __('Trouvées', 'chassesautresor-com'),
            (string) (int) ($row['trouves'] ?? 0)
        );
        $html .= $this->metric(
            __('Tentatives', 'chassesautresor-com'),
            (string) (int) ($row['tentatives'] ?? 0)
        );
        $html .= $this->metric(__('Étapes', 'chassesautresor-com'), $this->stepsLabel($row));
        if (is_array($row['yield'] ?? null)) {
            $html .= $this->metric(__('Rendement', 'chassesautresor-com'), $this->yieldLabel((array) $row['yield']));
        }
        if (array_key_exists('pending', $row)) {
            $html .= $this->metric(__('En attente', 'chassesautresor-com'), (string) (int) $row['pending']);
        }
        if (array_key_exists('median_attempts', $row)) {
            $median = $row['median_attempts'];
            $html .= $this->metric(
                __('Médiane pour résoudre', 'chassesautresor-com'),
                $median === null ? '—' : (string) (int) $median
            );
        }
        $html .= '</dl>';
        if (is_array($row['top_hint'] ?? null)) {
            $hint = (array) $row['top_hint'];
            $html .= '<p class="progression-hint">' . esc_html(sprintf(
                /* translators: 1: hint title, 2: number of unlocks */
                __('Indice le plus débloqué : %1$s (%2$d)', 'chassesautresor-com'),
                (string) ($hint['title'] ?? ''),
                (int) ($hint['unlocks'] ?? 0)
            )) . '</p>';
        }
        $html .= $this->stepsDetail((array) ($row['steps_detail'] ?? []));
        $html .= '<div class="myaccount-riddle-stats-ranking">';
        $html .= '<span class="myaccount-riddle-stats-ranking__label">'
            . esc_html(__('Classement', 'chassesautresor-com')) . '</span>';
        $html .= '<span class="myaccount-riddle-stats-ranking__value">'
            . esc_html($this->rankingLabel((array) ($row['ranking'] ?? []))) . '</span>';
        $html .= '</div></li>';

        return $html;
    }

    /** @param array<string, mixed> $funnel */
    private function funnel(array $funnel): string
    {
        $steps = [
            __('Engagés', 'chassesautresor-com') => (int) ($funnel['engaged'] ?? 0),
        ];
        if ($funnel['with_step'] !== null && array_key_exists('with_step', $funnel)) {
            $steps[__('Une étape', 'chassesautresor-com')] = (int) $funnel['with_step'];
        }
        $steps[__('Soumis', 'chassesautresor-com')] = (int) ($funnel['submitted'] ?? 0);
        $steps[__('Résolus', 'chassesautresor-com')] = (int) ($funnel['solved'] ?? 0);

        $html = '<ol class="progression-funnel">';
        foreach ($steps as $label => $value) {
            $html .= '<li><span>' . esc_html((string) $label) . '</span><strong>'
                . esc_html((string) $value) . '</strong></li>';
        }

        return $html . '</ol>';
    }

    /** @param array<int, array<string, mixed>> $steps */
    private function stepsDetail(array $steps): string
    {
        if ($steps === []) {
            return '';
        }

        $html = '<details class="progression-steps"><summary>'
            . esc_html(__('Étapes', 'chassesautresor-com')) . '</summary><ul>';
        foreach ($steps as $step) {
            $title = trim((string) ($step['title'] ?? ''));
            if ($title === '') {
                $title = __('Étape', 'chassesautresor-com');
            }
            $attempts = (int) ($step['attempts'] ?? 0);
            $meta = sprintf(
                /* translators: 1: players who reached the step, 2: engaged players, 3: attempts on the step */
                __('%1$d/%2$d joueurs · %3$s', 'chassesautresor-com'),
                (int) ($step['reached'] ?? 0),
                (int) ($step['engaged'] ?? 0),
                sprintf(
                    _n('%d tentative', '%d tentatives', $attempts, 'chassesautresor-com'),
                    $attempts
                )
            );
            $html .= '<li><span class="progression-steps__title">' . esc_html($title) . '</span>'
                . '<span class="progression-steps__meta">' . esc_html($meta) . '</span></li>';
        }

        return $html . '</ul></details>';
    }

    /** @param array<string, mixed> $yield */
    private function yieldLabel(array $yield): string
    {
        $success = (int) ($yield['success'] ?? 0);
        $total = (int) ($yield['total'] ?? 0);
        if ($total <= 0) {
            return '—';
        }

        $percent = (int) round((100 * $success) / $total);

        return $success . '/' . $total . ' · ' . $percent . ' %';
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
            $label = ($index + 1) . '. ' . $name;
            $details = [];
            if (array_key_exists('tentatives', $solver)) {
                $attempts = (int) $solver['tentatives'];
                $details[] = sprintf(
                    _n('%d tentative', '%d tentatives', $attempts, 'chassesautresor-com'),
                    $attempts
                );
            }
            $date = $this->formatDate((string) ($solver['date'] ?? ''));
            if ($date !== '') {
                $details[] = $date;
            }
            if ($details !== []) {
                $label .= ' (' . implode(', ', $details) . ')';
            }
            $parts[] = $label;
        }

        return $parts === [] ? '—' : implode(' · ', $parts);
    }

    private function formatDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '';
        }

        if (function_exists('wp_date')) {
            return (string) wp_date('d/m/Y', $timestamp);
        }

        return date('d/m/Y', $timestamp);
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
