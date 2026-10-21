<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/** Render a player's hunt progression: four indicators and one row per riddle. */
final class PlayerProgressionRenderer
{
    /**
     * @param array<string, mixed> $overview
     * @param array<int, array{id:int,title:string,url:string}> $hunts
     */
    public function render(array $overview, array $hunts = [], int $currentHuntId = 0): string
    {
        ob_start();
        echo '<section class="progression-player">';
        $this->renderHuntSwitcher($hunts, $currentHuntId);
        $this->renderCards($overview);
        $this->renderRiddles((array) ($overview['riddles'] ?? []));
        echo '</section>';

        return trim((string) ob_get_clean());
    }

    /** @param array<int, array{id:int,title:string,url:string}> $hunts */
    private function renderHuntSwitcher(array $hunts, int $currentHuntId): void
    {
        if (count($hunts) < 2) {
            return;
        }

        echo '<nav class="progression-hunts" aria-label="'
            . esc_attr(__('Chasse affichée', 'chassesautresor-com')) . '">';
        foreach ($hunts as $hunt) {
            $huntId = (int) ($hunt['id'] ?? 0);
            $title = trim((string) ($hunt['title'] ?? ''));
            if ($huntId <= 0 || $title === '') {
                continue;
            }
            $current = $huntId === $currentHuntId ? ' aria-current="page"' : '';
            echo '<a class="progression-hunts__link" href="'
                . esc_url((string) ($hunt['url'] ?? '')) . '"' . $current . '>'
                . esc_html($title) . '</a>';
        }
        echo '</nav>';
    }

    /** @param array<string, mixed> $overview */
    private function renderCards(array $overview): void
    {
        $pending = (int) ($overview['attempts_pending'] ?? 0);
        $attemptCaption = $pending > 0
            ? sprintf(
                /* translators: %d: attempts waiting for a manual review */
                _n(
                    '%d en attente de validation',
                    '%d en attente de validation',
                    $pending,
                    'chassesautresor-com'
                ),
                $pending
            )
            : __('Bonnes réponses', 'chassesautresor-com');

        $cards = [
            [
                'label' => __('Résolues', 'chassesautresor-com'),
                'value' => $this->ratio(
                    (int) ($overview['resolved'] ?? 0),
                    (int) ($overview['resolvable'] ?? 0)
                ),
                'caption' => __('Énigmes validables', 'chassesautresor-com'),
            ],
            [
                'label' => __('Engagées', 'chassesautresor-com'),
                'value' => $this->ratio(
                    (int) ($overview['engaged'] ?? 0),
                    (int) ($overview['total'] ?? 0)
                ),
                'caption' => __('Énigmes de la chasse', 'chassesautresor-com'),
            ],
            [
                'label' => __('Tentatives', 'chassesautresor-com'),
                'value' => $this->ratio(
                    (int) ($overview['attempts_success'] ?? 0),
                    (int) ($overview['attempts_total'] ?? 0)
                ),
                'caption' => $attemptCaption,
            ],
            [
                'label' => __('Indices', 'chassesautresor-com'),
                'value' => (string) (int) ($overview['hints'] ?? 0),
                'caption' => __('Débloqués sur cette chasse', 'chassesautresor-com'),
            ],
        ];

        echo '<section class="team-stats" aria-label="'
            . esc_attr(__('Votre progression', 'chassesautresor-com')) . '">';
        echo '<h2 class="screen-reader-text">'
            . esc_html(__('Votre progression', 'chassesautresor-com')) . '</h2>';
        echo '<div class="team-stats__grid">';
        foreach ($cards as $card) {
            echo '<article class="team-stat">';
            echo '<p class="team-stat__label">' . esc_html($card['label']) . '</p>';
            echo '<p class="team-stat__value">' . esc_html($card['value']) . '</p>';
            echo '<p class="team-stat__caption">' . esc_html($card['caption']) . '</p>';
            echo '</article>';
        }
        echo '</div></section>';
    }

    /** @param array<int, array<string, mixed>> $riddles */
    private function renderRiddles(array $riddles): void
    {
        echo '<section class="progression-riddles" aria-label="'
            . esc_attr(__('Énigmes', 'chassesautresor-com')) . '">';
        echo '<h2 class="progression-riddles__title">'
            . esc_html(__('Énigmes', 'chassesautresor-com')) . '</h2>';
        if ($riddles === []) {
            echo '<p class="progression-riddles__empty">'
                . esc_html(__('Cette chasse ne contient pas encore d’énigme.', 'chassesautresor-com'))
                . '</p></section>';
            return;
        }

        echo '<ol class="progression-riddles__list">';
        foreach ($riddles as $riddle) {
            $this->renderRiddle($riddle);
        }
        echo '</ol></section>';
    }

    /** @param array<string, mixed> $riddle */
    private function renderRiddle(array $riddle): void
    {
        $status = (string) ($riddle['status'] ?? 'non_commencee');
        $statusClass = preg_replace('/[^a-z_]/', '', $status) ?: 'non_commencee';
        $title = trim((string) ($riddle['title'] ?? ''));
        if ($title === '') {
            $title = __('Énigme', 'chassesautresor-com');
        }
        $url = trim((string) ($riddle['url'] ?? ''));
        $stepsTotal = (int) ($riddle['steps_total'] ?? 0);
        $lastActivity = $this->formatDate((string) ($riddle['last_activity'] ?? ''));
        $activityLabel = $lastActivity !== ''
            ? sprintf(
                /* translators: %s: localized date of the latest attempt or step */
                __('Dernière activité le %s', 'chassesautresor-com'),
                $lastActivity
            )
            : __('Pas encore d’activité', 'chassesautresor-com');
        $solved = in_array($status, ['resolue', 'terminee'], true);
        $action = $solved
            ? __('Voir l’énigme', 'chassesautresor-com')
            : __('Reprendre', 'chassesautresor-com');

        echo '<li class="progression-riddle">';
        echo '<div class="progression-riddle__main">';
        echo '<h3 class="progression-riddle__title">';
        if ($url !== '') {
            echo '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>';
        } else {
            echo esc_html($title);
        }
        echo '</h3>';
        echo '<p class="progression-riddle__status progression-riddle__status--' . esc_attr($statusClass) . '">'
            . esc_html($this->statusLabel($status)) . '</p>';
        echo '</div>';
        echo '<dl class="progression-riddle__metrics">';
        if ($stepsTotal > 0) {
            echo '<div><dt>' . esc_html(__('Étapes trouvées', 'chassesautresor-com')) . '</dt><dd>'
                . esc_html((string) (int) ($riddle['steps_done'] ?? 0)) . '</dd></div>';
        }
        echo '<div><dt>' . esc_html(__('Tentatives', 'chassesautresor-com')) . '</dt><dd>'
            . esc_html((string) (int) ($riddle['attempts'] ?? 0)) . '</dd></div>';
        echo '</dl>';
        echo '<p class="progression-riddle__activity">' . esc_html($activityLabel) . '</p>';
        if ($url !== '') {
            echo '<a class="progression-riddle__action" href="' . esc_url($url) . '">'
                . esc_html($action) . '</a>';
        }
        echo '</li>';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'en_cours' => __('En cours', 'chassesautresor-com'),
            'soumis' => __('En attente de validation', 'chassesautresor-com'),
            'resolue', 'terminee' => __('Résolue', 'chassesautresor-com'),
            'abandonnee' => __('Abandonnée', 'chassesautresor-com'),
            'echouee' => __('Échouée', 'chassesautresor-com'),
            default => __('Pas commencée', 'chassesautresor-com'),
        };
    }

    private function ratio(int $left, int $right): string
    {
        return $left . '/' . $right;
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
}
