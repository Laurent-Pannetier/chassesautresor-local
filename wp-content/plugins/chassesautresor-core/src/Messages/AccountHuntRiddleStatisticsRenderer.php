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
        echo '<section class="progression-organizer">';
        if ($this->dashboardProvider !== null) {
            echo $this->periodNav($period, $pageUrl);
            echo $this->summaryMetrics($summary);
            echo $this->periodNote();
        }
        echo '<section class="progression-riddles" aria-label="'
            . esc_attr(__('Énigmes', 'chassesautresor-com')) . '">';
        echo '<h2 class="progression-riddles__title">' . esc_html(__('Énigmes', 'chassesautresor-com')) . '</h2>';
        if ($rows === []) {
            echo '<p class="progression-riddles__empty">' . esc_html(__(
                'Aucune énigme associée à cette chasse pour le moment.',
                'chassesautresor-com'
            )) . '</p>';
        } else {
            echo '<ol class="progression-riddles__list">';
            foreach ($rows as $row) {
                if (is_array($row)) {
                    echo $this->riddleItem($row);
                }
            }
            echo '</ol>';
        }
        echo '</section></section>';

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
            'Les tentatives, le rendement, la file d’attente et la courbe suivent la période. '
            . 'Les joueurs résolus, en validation ou dans les étapes décrivent l’état actuel.',
            'chassesautresor-com'
        )) . '</p>';
    }

    /** @param array<string, mixed> $summary */
    private function summaryMetrics(array $summary): string
    {
        $winners = (int) ($summary['winners'] ?? 0);
        $cap = (int) ($summary['winner_cap'] ?? 0);
        $winnerValue = $cap > 0 ? $winners . '/' . $cap : (string) $winners;
        $cards = [
            [
                __('Participants', 'chassesautresor-com'),
                (string) (int) ($summary['participants'] ?? 0),
                __('Inscrits sur la chasse', 'chassesautresor-com'),
            ],
            [
                __('Engagement', 'chassesautresor-com'),
                (int) ($summary['engagement_rate'] ?? 0) . ' %',
                __('Part des énigmes engagées', 'chassesautresor-com'),
            ],
            [
                __('Résolution', 'chassesautresor-com'),
                (int) ($summary['resolution_rate'] ?? 0) . ' %',
                __('Parmi les énigmes engagées', 'chassesautresor-com'),
            ],
            [
                __('Tentatives', 'chassesautresor-com'),
                (string) (int) ($summary['tentatives'] ?? 0),
                __('Sur la période', 'chassesautresor-com'),
            ],
            [
                __('Gagnants', 'chassesautresor-com'),
                $winnerValue,
                __('Places attribuées', 'chassesautresor-com'),
            ],
        ];

        $html = '<section class="team-stats" aria-label="'
            . esc_attr(__('Synthèse de la chasse', 'chassesautresor-com')) . '">';
        $html .= '<h2 class="screen-reader-text">'
            . esc_html(__('Synthèse de la chasse', 'chassesautresor-com')) . '</h2>';
        $html .= '<div class="team-stats__grid">';
        foreach ($cards as $card) {
            $html .= '<article class="team-stat">';
            $html .= '<p class="team-stat__label">' . esc_html($card[0]) . '</p>';
            $html .= '<p class="team-stat__value">' . esc_html($card[1]) . '</p>';
            $html .= '<p class="team-stat__caption">' . esc_html($card[2]) . '</p>';
            $html .= '</article>';
        }

        return $html . '</div></section>';
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
        $stepCount = (int) ($row['steps'] ?? 0);
        if ($stepCount <= 0 && is_array($row['steps_detail'] ?? null)) {
            $stepCount = count($row['steps_detail']);
        }
        $engaged = (int) ($row['funnel']['engaged'] ?? $row['participants'] ?? 0);
        $solved = (int) ($row['funnel']['solved'] ?? $row['trouves'] ?? 0);
        $submitted = (int) ($row['funnel']['submitted'] ?? 0);
        $withStep = $this->withStepCount($row, $stepCount);

        $html = '<li class="progression-riddle">';
        $html .= '<div class="progression-riddle__main">';
        $html .= '<h3 class="progression-riddle__title">'
            . esc_html((string) ($row['title'] ?? '')) . '</h3>';
        $html .= '</div>';
        $html .= '<p class="progression-story">' . esc_html($this->story(
            $row,
            $engaged,
            $solved,
            $submitted,
            $withStep,
            $stepCount
        )) . '</p>';
        $html .= $this->distributionBar(
            $this->distribution($engaged, (int) $withStep, $submitted, $solved),
            $withStep !== null
        );
        $html .= $this->keyFigures($row);
        if (is_array($row['top_hint'] ?? null)) {
            $hint = (array) $row['top_hint'];
            $html .= '<p class="progression-hint">' . esc_html(sprintf(
                /* translators: 1: hint title, 2: number of unlocks */
                __('Indice le plus débloqué : %1$s (%2$d)', 'chassesautresor-com'),
                (string) ($hint['title'] ?? ''),
                (int) ($hint['unlocks'] ?? 0)
            )) . '</p>';
        }
        $html .= $this->stepsBlock($row, $engaged);
        $html .= $this->seriesChart($row);
        $html .= '</li>';

        return $html;
    }

    /** @param array<string, mixed> $row */
    private function withStepCount(array $row, int $stepCount): ?int
    {
        if (is_array($row['funnel'] ?? null) && array_key_exists('with_step', $row['funnel'])) {
            return $row['funnel']['with_step'] === null ? null : (int) $row['funnel']['with_step'];
        }

        return $stepCount > 0 ? (int) ($row['step_players'] ?? 0) : null;
    }

    /** @param array<string, mixed> $row */
    private function story(
        array $row,
        int $engaged,
        int $solved,
        int $submitted,
        ?int $withStep,
        int $stepCount
    ): string {
        $sentences = [];
        if ($engaged === 0) {
            $sentences[] = __('Aucun joueur engagé sur la période.', 'chassesautresor-com');
        } else {
            $sentences[] = sprintf(
                /* translators: %d: number of engaged players */
                _n('%d joueur engagé.', '%d joueurs engagés.', $engaged, 'chassesautresor-com'),
                $engaged
            );
        }

        if ($withStep === 0 && $stepCount > 0) {
            $sentences[] = sprintf(
                /* translators: %d: number of intermediate steps */
                _n(
                    'L’étape n’a pas été franchie.',
                    'Aucune des %d étapes n’a été franchie.',
                    $stepCount,
                    'chassesautresor-com'
                ),
                $stepCount
            );
        } elseif ($withStep !== null && $withStep > 0) {
            $sentences[] = sprintf(
                /* translators: %d: number of players who cleared an intermediate step */
                _n(
                    '%d joueur a franchi au moins une étape.',
                    '%d joueurs ont franchi au moins une étape.',
                    $withStep,
                    'chassesautresor-com'
                ),
                $withStep
            );
        }

        $finalAttempts = is_array($row['yield'] ?? null)
            ? (int) ($row['yield']['total'] ?? 0)
            : (int) ($row['tentatives'] ?? 0);
        if ($solved > 0) {
            $sentences[] = sprintf(
                /* translators: %d: number of players who solved the riddle */
                _n(
                    '%d joueur a résolu l’énigme.',
                    '%d joueurs ont résolu l’énigme.',
                    $solved,
                    'chassesautresor-com'
                ),
                $solved
            );
        } elseif ($finalAttempts > 0 || $submitted > 0) {
            $sentences[] = __('Personne n’a encore résolu l’énigme.', 'chassesautresor-com');
        } else {
            $sentences[] = __('Aucune réponse finale n’a été envoyée.', 'chassesautresor-com');
        }

        if ($submitted > 0) {
            $sentences[] = sprintf(
                /* translators: %d: number of players waiting for a manual validation */
                _n(
                    '%d joueur attend une validation.',
                    '%d joueurs attendent une validation.',
                    $submitted,
                    'chassesautresor-com'
                ),
                $submitted
            );
        }

        return implode(' ', $sentences);
    }

    /**
     * Partition of the engaged players. Counts that describe another time
     * scope are clamped so the bar never exceeds the engaged total.
     *
     * @return array{engaged:int,solved:int,review:int,in_steps:int,not_started:int}
     */
    private function distribution(int $engaged, int $withStep, int $submitted, int $solved): array
    {
        $engaged = max(0, $engaged);
        $review = min(max(0, $submitted), $engaged);
        $remaining = $engaged - $review;
        $solvedIn = min(max(0, $solved), $remaining);
        $remaining -= $solvedIn;
        $stillInSteps = max(0, $withStep - max(0, $solved) - max(0, $submitted));
        $inSteps = min($stillInSteps, $remaining);

        return [
            'engaged' => $engaged,
            'solved' => $solvedIn,
            'review' => $review,
            'in_steps' => $inSteps,
            'not_started' => $remaining - $inSteps,
        ];
    }

    /** @param array{engaged:int,solved:int,review:int,in_steps:int,not_started:int} $distribution */
    private function distributionBar(array $distribution, bool $hasSteps): string
    {
        if ($distribution['engaged'] <= 0) {
            return '';
        }

        $notStarted = $hasSteps
            ? __('Pas encore d’étape', 'chassesautresor-com')
            : __('En cours', 'chassesautresor-com');
        $segments = [
            'solved' => [__('Résolus', 'chassesautresor-com'), $distribution['solved']],
            'review' => [__('En validation', 'chassesautresor-com'), $distribution['review']],
            'in_steps' => [__('Dans les étapes', 'chassesautresor-com'), $distribution['in_steps']],
            'not_started' => [$notStarted, $distribution['not_started']],
        ];
        $visible = array_filter(
            $segments,
            static fn (array $segment): bool => $segment[1] > 0
        );
        if ($visible === []) {
            return '';
        }

        $html = '<figure class="progression-mix">';
        $html .= '<div class="progression-mix__bar" aria-hidden="true">';
        foreach ($visible as $key => $segment) {
            $html .= '<span class="progression-mix__segment progression-mix__segment--' . esc_attr((string) $key)
                . '" style="flex:' . (int) $segment[1] . ' 1 0"></span>';
        }
        $html .= '</div><ul class="progression-mix__legend">';
        foreach ($visible as $key => $segment) {
            $html .= '<li><i class="progression-mix__swatch progression-mix__segment--' . esc_attr((string) $key)
                . '" aria-hidden="true"></i> ' . esc_html((string) $segment[0]) . ' <strong>'
                . esc_html((string) $segment[1]) . '</strong></li>';
        }

        return $html . '</ul></figure>';
    }

    /** @param array<string, mixed> $row */
    private function keyFigures(array $row): string
    {
        $items = [];
        if (is_array($row['yield'] ?? null)) {
            $yield = $this->yieldLabel((array) $row['yield']);
            if ($yield !== '—') {
                $items[] = [__('Rendement', 'chassesautresor-com'), $yield];
            }
        }
        $pending = (int) ($row['pending'] ?? 0);
        if ($pending > 0) {
            $items[] = [
                __('En attente', 'chassesautresor-com'),
                sprintf(
                    /* translators: %d: final answers still waiting for review */
                    _n('%d réponse', '%d réponses', $pending, 'chassesautresor-com'),
                    $pending
                ),
            ];
        }
        if (array_key_exists('median_attempts', $row) && $row['median_attempts'] !== null) {
            $median = (int) $row['median_attempts'];
            $items[] = [
                __('Médiane pour résoudre', 'chassesautresor-com'),
                sprintf(
                    /* translators: %d: median number of final attempts needed to solve */
                    _n('%d tentative', '%d tentatives', $median, 'chassesautresor-com'),
                    $median
                ),
            ];
        }
        if ($items === []) {
            return '';
        }

        $html = '<dl class="progression-figures">';
        foreach ($items as $item) {
            $html .= '<div><dt>' . esc_html($item[0]) . '</dt><dd>' . esc_html($item[1]) . '</dd></div>';
        }

        return $html . '</dl>';
    }

    /** @param array<string, mixed> $row */
    private function stepsBlock(array $row, int $engaged): string
    {
        $steps = [];
        foreach ((array) ($row['steps_detail'] ?? []) as $step) {
            if (is_array($step)) {
                $steps[] = $step;
            }
        }
        if ($steps === []) {
            return '';
        }

        $anyReached = false;
        $anyAttempts = false;
        foreach ($steps as $step) {
            if ((int) ($step['reached'] ?? 0) > 0) {
                $anyReached = true;
            }
            if ((int) ($step['attempts'] ?? 0) > 0) {
                $anyAttempts = true;
            }
        }
        if (!$anyReached && !$anyAttempts) {
            return '';
        }

        $title = $anyReached
            ? __('Joueurs arrivés à chaque étape', 'chassesautresor-com')
            : __('Tentatives par étape', 'chassesautresor-com');
        $html = '<section class="progression-dropoff">';
        $html .= '<h4>' . esc_html($title) . '</h4>';
        if ($anyReached) {
            $html .= $this->stepsChart($steps, $engaged);
        }
        $html .= $this->stepsTable($steps, $engaged);

        return $html . '</section>';
    }

    /** @param array<int, array<string, mixed>> $steps */
    private function stepsChart(array $steps, int $engaged): string
    {
        $scale = max(1, $engaged);
        foreach ($steps as $step) {
            $scale = max($scale, (int) ($step['reached'] ?? 0));
        }

        $html = '<ul class="progression-steps-chart">';
        foreach ($steps as $step) {
            $reached = (int) ($step['reached'] ?? 0);
            $percent = (int) round((100 * $reached) / $scale);
            $label = $this->stepTitle($step);
            $html .= '<li class="progression-steps-chart__row">';
            $html .= '<span class="progression-steps-chart__label">' . esc_html($label) . '</span>';
            $html .= '<span class="progression-steps-chart__track" aria-hidden="true">';
            $html .= '<span class="progression-steps-chart__fill" style="width:' . $percent . '%"></span>';
            $html .= '</span>';
            $html .= '<span class="progression-steps-chart__value">' . esc_html((string) $reached) . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '<p class="progression-note">' . esc_html(sprintf(
            /* translators: %d: number of players represented by a full bar */
            _n(
                'La barre pleine représente %d joueur.',
                'La barre pleine représente %d joueurs.',
                $scale,
                'chassesautresor-com'
            ),
            $scale
        )) . '</p>';

        return $html;
    }

    /** @param array<int, array<string, mixed>> $steps */
    private function stepsTable(array $steps, int $engaged): string
    {
        $breakIndex = $this->steepestDropIndex($steps);
        $html = '<div class="progression-dropoff__scroll"><table class="progression-dropoff__table">';
        $html .= '<thead><tr>';
        foreach ([
            __('Étape', 'chassesautresor-com'),
            __('Joueurs', 'chassesautresor-com'),
            __('Part des engagés', 'chassesautresor-com'),
            __('Tentatives', 'chassesautresor-com'),
        ] as $header) {
            $html .= '<th scope="col">' . esc_html($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($steps as $index => $step) {
            $reached = (int) ($step['reached'] ?? 0);
            $attempts = (int) ($step['attempts'] ?? 0);
            $share = $engaged > 0 ? (int) round((100 * $reached) / $engaged) . ' %' : '—';
            $class = $index === $breakIndex ? ' class="progression-dropoff__break"' : '';
            $html .= '<tr' . $class . '><th scope="row">' . esc_html($this->stepTitle($step)) . '</th>';
            $html .= '<td>' . esc_html((string) $reached) . '</td>';
            $html .= '<td>' . esc_html($share) . '</td>';
            $html .= '<td>' . esc_html((string) $attempts) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        if ($breakIndex !== null) {
            $html .= '<p class="progression-note">' . esc_html(sprintf(
                /* translators: %s: step title where the largest drop happens */
                __('La plus forte chute de joueurs est à « %s ».', 'chassesautresor-com'),
                $this->stepTitle($steps[$breakIndex])
            )) . '</p>';
        }

        return $html;
    }

    /** @param array<int, array<string, mixed>> $steps */
    private function steepestDropIndex(array $steps): ?int
    {
        $breakIndex = null;
        $largestDrop = 1;
        $previous = null;
        foreach ($steps as $index => $step) {
            $reached = (int) ($step['reached'] ?? 0);
            if ($previous !== null && ($previous - $reached) > $largestDrop) {
                $largestDrop = $previous - $reached;
                $breakIndex = $index;
            }
            $previous = $reached;
        }

        return $breakIndex;
    }

    /** @param array<string, mixed> $step */
    private function stepTitle(array $step): string
    {
        $title = trim((string) ($step['title'] ?? ''));

        return $title !== '' ? $title : __('Étape', 'chassesautresor-com');
    }

    /** @param array<string, mixed> $row */
    private function seriesChart(array $row): string
    {
        $series = [];
        foreach ((array) ($row['attempt_series'] ?? []) as $point) {
            if (!is_array($point)) {
                continue;
            }
            $series[] = [
                'label' => (string) ($point['label'] ?? ''),
                'total' => (int) ($point['total'] ?? 0),
                'success' => (int) ($point['success'] ?? 0),
            ];
        }
        if (count($series) < 2) {
            return '';
        }

        $totals = array_map(static fn (array $point): int => $point['total'], $series);
        $successes = array_map(static fn (array $point): int => $point['success'], $series);
        $max = max(1, max($totals));
        $left = 36.0;
        $right = 628.0;
        $top = 16.0;
        $bottom = 132.0;
        $chartId = 'progression-series-' . max(0, (int) ($row['id'] ?? 0));
        $grain = (string) ($row['attempt_series_grain'] ?? 'week');
        $caption = $grain === 'day'
            ? __('Réponses finales par jour', 'chassesautresor-com')
            : __('Réponses finales par semaine', 'chassesautresor-com');
        $summary = sprintf(
            /* translators: 1: number of points, 2: final attempts, 3: correct final answers */
            __(
                'Courbe des réponses finales : %1$d points, %2$d tentatives, %3$d bonnes réponses.',
                'chassesautresor-com'
            ),
            count($series),
            array_sum($totals),
            array_sum($successes)
        );

        $html = '<figure class="progression-series">';
        $html .= '<figcaption>' . esc_html($caption) . '</figcaption>';
        $html .= '<svg viewBox="0 0 640 176" role="img" aria-labelledby="' . esc_attr($chartId) . '">';
        $html .= '<title id="' . esc_attr($chartId) . '">' . esc_html($summary) . '</title>';
        $html .= '<line class="progression-series__grid" x1="' . $left . '" y1="' . $bottom
            . '" x2="' . $right . '" y2="' . $bottom . '"></line>';
        $html .= '<line class="progression-series__grid" x1="' . $left . '" y1="' . $top
            . '" x2="' . $right . '" y2="' . $top . '"></line>';
        $html .= '<text x="0" y="20">' . esc_html((string) $max) . '</text>';
        $html .= '<text x="0" y="130">0</text>';
        $html .= '<polyline class="progression-series__line progression-series__line--total" points="'
            . esc_attr($this->chartPoints($totals, $max, $left, $right, $top, $bottom)) . '"></polyline>';
        $html .= '<polyline class="progression-series__line progression-series__line--success" points="'
            . esc_attr($this->chartPoints($successes, $max, $left, $right, $top, $bottom)) . '"></polyline>';
        $html .= $this->seriesLabels($series, $left, $right);
        $html .= '</svg>';
        $html .= '<ul class="progression-series__legend">';
        $html .= '<li><i class="progression-series__swatch progression-series__swatch--total" aria-hidden="true"></i> '
            . esc_html(__('Tentatives finales', 'chassesautresor-com')) . '</li>';
        $html .= '<li><i class="progression-series__swatch progression-series__swatch--success"'
            . ' aria-hidden="true"></i> '
            . esc_html(__('Bonnes réponses', 'chassesautresor-com')) . '</li>';
        $html .= '</ul>';
        $html .= '<p class="progression-note">' . esc_html(__(
            'La courbe suit la période choisie. Les essais sur les étapes n’y entrent pas.',
            'chassesautresor-com'
        )) . '</p></figure>';

        return $html;
    }

    /**
     * @param int[] $values
     */
    private function chartPoints(
        array $values,
        int $max,
        float $left,
        float $right,
        float $top,
        float $bottom
    ): string {
        $span = max(1, count($values) - 1);
        $coords = [];
        foreach (array_values($values) as $index => $value) {
            $x = $left + ($right - $left) * ($index / $span);
            $y = $bottom - ($bottom - $top) * ($value / max(1, $max));
            $coords[] = round($x, 1) . ',' . round($y, 1);
        }

        return implode(' ', $coords);
    }

    /** @param array<int, array{label:string,total:int,success:int}> $series */
    private function seriesLabels(array $series, float $left, float $right): string
    {
        $count = count($series);
        $step = (int) max(1, ceil($count / 8));
        $span = max(1, $count - 1);
        $html = '';
        foreach ($series as $index => $point) {
            $isLast = $index === $count - 1;
            if ($index % $step !== 0 && !$isLast) {
                continue;
            }
            $x = round($left + ($right - $left) * ($index / $span), 1);
            $anchor = $index === 0 ? 'start' : ($isLast ? 'end' : 'middle');
            $html .= '<text x="' . $x . '" y="152" text-anchor="' . $anchor . '">'
                . esc_html($point['label']) . '</text>';
        }

        return $html;
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

    private function emptyCard(string $title, string $message): string
    {
        return '<section class="progression-organizer">'
            . '<h2 class="progression-riddles__title">' . esc_html($title) . '</h2>'
            . '<p class="progression-riddles__empty">' . esc_html($message) . '</p>'
            . '</section>';
    }
}
