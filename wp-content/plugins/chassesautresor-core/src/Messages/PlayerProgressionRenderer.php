<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/** Render a player's progress, hunt leaderboard and per-riddle activity. */
final class PlayerProgressionRenderer
{
    /**
     * @param array<string, mixed> $overview
     * @param array<int, array{id:int,title:string,url:string}> $hunts
     */
    public function render(
        array $overview,
        array $hunts = [],
        int $currentHuntId = 0,
        array $ajax = []
    ): string
    {
        ob_start();
        echo '<section class="progression-player"';
        if ($ajax !== []) {
            echo ' data-progression-player data-ajax-url="' . esc_url((string) ($ajax['url'] ?? '')) . '"';
            echo ' data-nonce="' . esc_attr((string) ($ajax['nonce'] ?? '')) . '"';
            echo ' data-hunt-id="' . esc_attr((string) $currentHuntId) . '"';
            echo ' data-error-message="'
                . esc_attr__('Impossible de charger ces données.', 'chassesautresor-com') . '"';
        }
        echo '>';
        $this->renderHuntSwitcher($hunts, $currentHuntId);
        echo '<div class="progression-overview">';
        $this->renderCards($overview);
        echo '<section class="progression-leaderboard" aria-labelledby="progression-leaderboard-title">';
        echo '<h2 id="progression-leaderboard-title">'
            . esc_html__('Classement de la chasse', 'chassesautresor-com') . '</h2>';
        echo '<div data-progression-leaderboard>';
        echo $this->leaderboard((array) ($overview['leaderboard'] ?? []));
        echo '</div></section></div>';
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
        echo '<section class="player-summary" aria-label="'
            . esc_attr(__('Votre progression', 'chassesautresor-com')) . '">';
        echo '<h2 class="screen-reader-text">'
            . esc_html(__('Votre progression', 'chassesautresor-com')) . '</h2>';
        echo '<article class="player-summary__primary">';
        echo '<p class="team-stat__label">' . esc_html__('Résolues', 'chassesautresor-com') . '</p>';
        echo '<p class="team-stat__value">' . esc_html($this->ratio(
            (int) ($overview['resolved'] ?? 0),
            (int) ($overview['resolvable'] ?? 0)
        )) . '</p></article>';
        echo '<div class="player-summary__compact">';
        $cards = [
            [__('Commencées', 'chassesautresor-com'), (int) ($overview['engaged'] ?? 0)],
            [__('Tentatives', 'chassesautresor-com'), (int) ($overview['attempts_total'] ?? 0)],
            [__('Indices', 'chassesautresor-com'), (int) ($overview['hints'] ?? 0)],
        ];
        foreach ($cards as [$label, $value]) {
            echo '<article class="player-summary__mini">';
            echo '<p class="team-stat__label">' . esc_html($label) . '</p>';
            echo '<p class="team-stat__value">' . esc_html((string) $value) . '</p>';
            echo '</article>';
        }
        echo '</div></section>';
    }

    /** @param array<string, mixed> $view */
    public function leaderboard(array $view): string
    {
        $rows = (array) ($view['rows'] ?? []);
        $page = max(1, (int) ($view['page'] ?? 1));
        $pages = max(1, (int) ($view['pages'] ?? 1));
        $currentUserId = (int) ($view['current_user_id'] ?? 0);
        $position = (int) ($view['current_position'] ?? 0);
        ob_start();
        if ($rows === []) {
            echo '<p class="progression-leaderboard__empty">'
                . esc_html__('Aucun joueur classé pour le moment.', 'chassesautresor-com') . '</p>';
        } else {
            echo '<ol class="progression-ranking-list" start="' . esc_attr((string) (($page - 1) * 10 + 1)) . '">';
            foreach ($rows as $index => $row) {
                $userId = (int) ($row['user_id'] ?? 0);
                $rank = (($page - 1) * 10) + $index + 1;
                $class = $userId === $currentUserId
                    ? ' class="progression-ranking-list__item is-current"'
                    : ' class="progression-ranking-list__item"';
                echo '<li' . $class . '>';
                echo '<span class="progression-ranking-list__rank">' . esc_html((string) $rank) . '</span>';
                echo $this->avatar($userId, 40);
                echo '<span class="progression-ranking-list__identity"><strong>'
                    . esc_html((string) ($row['username'] ?? '')) . '</strong><small>'
                    . esc_html($this->teamLabel((string) ($row['team_name'] ?? ''))) . '</small></span>';
                $resolved = (int) ($row['resolved'] ?? 0);
                echo '<span class="progression-ranking-list__score">' . esc_html(sprintf(
                    _n('%d résolue', '%d résolues', $resolved, 'chassesautresor-com'),
                    $resolved
                )) . '</span></li>';
            }
            echo '</ol>';
        }
        if ($position > 0 && ($position < (($page - 1) * 10 + 1) || $position > $page * 10)) {
            echo '<p class="progression-leaderboard__position">' . esc_html(sprintf(
                /* translators: %d: current player's rank */
                __('Votre position : %de', 'chassesautresor-com'),
                $position
            )) . '</p>';
        }
        echo $this->numberedPager($page, $pages);

        return trim((string) ob_get_clean());
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
        $thumbnailUrl = trim((string) ($riddle['thumbnail_url'] ?? ''));
        $stepsTotal = (int) ($riddle['steps_total'] ?? 0);
        $lastActivity = $this->formatDate((string) ($riddle['last_activity'] ?? ''));
        $activityLabel = $lastActivity !== ''
            ? sprintf(
                /* translators: %s: localized date of the latest attempt or step */
                __('Dernière activité le %s', 'chassesautresor-com'),
                $lastActivity
            )
            : __('Pas encore d’activité', 'chassesautresor-com');
        echo '<li class="progression-riddle">';
        echo '<div class="progression-riddle__personal">';
        if ($url !== '') {
            echo '<a class="progression-riddle__thumbnail" href="' . esc_url($url) . '">';
        } else {
            echo '<span class="progression-riddle__thumbnail">';
        }
        if ($thumbnailUrl !== '') {
            echo '<img src="' . esc_url($thumbnailUrl) . '" alt="">';
        } else {
            echo '<span aria-hidden="true">' . esc_html(mb_substr($title, 0, 1)) . '</span>';
        }
        echo $url !== '' ? '</a>' : '</span>';
        echo '<div class="progression-riddle__content"><div class="progression-riddle__main">';
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
            echo '<div><dt>' . esc_html(__('Étapes franchies', 'chassesautresor-com')) . '</dt><dd>'
                . esc_html((string) (int) ($riddle['steps_done'] ?? 0)) . '</dd></div>';
        }
        echo '<div><dt>' . esc_html(__('Tentatives', 'chassesautresor-com')) . '</dt><dd>'
            . esc_html((string) (int) ($riddle['attempts'] ?? 0)) . '</dd></div>';
        echo '</dl>';
        echo '<p class="progression-riddle__activity">' . esc_html($activityLabel) . '</p>';
        $attempts = (int) ($riddle['attempts'] ?? 0);
        if ($attempts > 0) {
            echo '<button type="button" class="progression-riddle__submissions"'
                . ' data-riddle-attempts="' . esc_attr((string) (int) ($riddle['id'] ?? 0)) . '"'
                . ' aria-expanded="false">' . esc_html(sprintf(
                    _n(
                        'Voir ma soumission',
                        'Voir mes %d soumissions',
                        $attempts,
                        'chassesautresor-com'
                    ),
                    $attempts
                )) . '</button>';
        }
        echo '</div></div>';
        $this->renderRiddleLeaders((array) ($riddle['leaders'] ?? []));
        echo '<div class="progression-riddle__attempts" data-riddle-attempts-panel hidden></div>';
        echo '</li>';
    }

    /** @param array<int, array<string, mixed>> $leaders */
    private function renderRiddleLeaders(array $leaders): void
    {
        if ($leaders === []) {
            return;
        }

        echo '<section class="progression-riddle__leaders" aria-label="'
            . esc_attr__('En tête sur cette énigme', 'chassesautresor-com') . '">';
        echo '<h4>' . esc_html__('En tête sur cette énigme', 'chassesautresor-com') . '</h4>';
        echo '<ol>';
        foreach ($leaders as $leader) {
            $userId = (int) ($leader['user_id'] ?? 0);
            echo '<li>' . $this->avatar($userId, 34);
            echo '<span><strong>' . esc_html((string) ($leader['username'] ?? '')) . '</strong><small>'
                . esc_html($this->teamLabel((string) ($leader['team_name'] ?? ''))) . '</small></span>';
            echo '<em>' . esc_html(
                !empty($leader['solved'])
                    ? __('Résolue', 'chassesautresor-com')
                    : __('En progression', 'chassesautresor-com')
            ) . '</em></li>';
        }
        echo '</ol></section>';
    }

    /** @param array<string, mixed> $view */
    public function attempts(array $view): string
    {
        $rows = (array) ($view['rows'] ?? []);
        $page = max(1, (int) ($view['page'] ?? 1));
        $pages = max(1, (int) ($view['pages'] ?? 1));
        ob_start();
        if ($rows === []) {
            echo '<p>' . esc_html__('Aucune soumission enregistrée.', 'chassesautresor-com') . '</p>';
        } else {
            echo '<div class="progression-attempts__table-wrap"><table class="progression-attempts__table">';
            echo '<thead><tr><th>' . esc_html__('Date', 'chassesautresor-com') . '</th><th>'
                . esc_html__('Proposition', 'chassesautresor-com') . '</th><th>'
                . esc_html__('Résultat', 'chassesautresor-com') . '</th></tr></thead><tbody>';
            foreach ($rows as $row) {
                $result = (string) ($row['resultat'] ?? '');
                echo '<tr><td>' . esc_html($this->formatDateTime((string) ($row['date_tentative'] ?? '')))
                    . '</td><td>' . esc_html((string) ($row['reponse_saisie'] ?? '')) . '</td><td><span class="'
                    . esc_attr('progression-attempts__result progression-attempts__result--' . $result) . '">'
                    . esc_html($this->attemptResultLabel($result, (int) ($row['traitee'] ?? 0)))
                    . '</span></td></tr>';
            }
            echo '</tbody></table></div>';
        }
        if ($pages > 1) {
            echo '<nav class="progression-attempts__pager" aria-label="'
                . esc_attr__('Pages des soumissions', 'chassesautresor-com') . '">';
            echo '<button type="button" data-attempt-page="' . esc_attr((string) max(1, $page - 1)) . '"'
                . ($page <= 1 ? ' disabled' : '') . '>'
                . esc_html__('Précédentes', 'chassesautresor-com') . '</button>';
            echo '<button type="button" data-attempt-page="' . esc_attr((string) min($pages, $page + 1)) . '"'
                . ($page >= $pages ? ' disabled' : '') . '>'
                . esc_html__('Suivantes', 'chassesautresor-com') . '</button></nav>';
        }

        return trim((string) ob_get_clean());
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

    private function avatar(int $userId, int $size): string
    {
        if ($userId > 0 && function_exists('get_avatar')) {
            return (string) get_avatar(
                $userId,
                $size,
                '',
                '',
                ['class' => 'progression-avatar', 'loading' => 'lazy']
            );
        }

        return '<span class="progression-avatar progression-avatar--empty" aria-hidden="true"></span>';
    }

    private function teamLabel(string $team): string
    {
        $team = trim($team);

        return $team !== '' ? $team : __('Solo', 'chassesautresor-com');
    }

    private function numberedPager(int $page, int $pages): string
    {
        if ($pages <= 1) {
            return '';
        }

        $html = '<nav class="progression-leaderboard__pager" aria-label="'
            . esc_attr__('Pages du classement', 'chassesautresor-com') . '">';
        $html .= '<button type="button" data-leaderboard-page="' . esc_attr((string) max(1, $page - 1))
            . '" aria-label="' . esc_attr__('Page précédente', 'chassesautresor-com') . '"'
            . ($page <= 1 ? ' disabled' : '') . '>&lsaquo;</button>';
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        if ($start > 1) {
            $html .= $this->pageButton(1, $page);
            if ($start > 2) {
                $html .= '<span aria-hidden="true">…</span>';
            }
        }
        for ($number = $start; $number <= $end; $number++) {
            $html .= $this->pageButton($number, $page);
        }
        if ($end < $pages) {
            if ($end < $pages - 1) {
                $html .= '<span aria-hidden="true">…</span>';
            }
            $html .= $this->pageButton($pages, $page);
        }
        $html .= '<button type="button" data-leaderboard-page="' . esc_attr((string) min($pages, $page + 1))
            . '" aria-label="' . esc_attr__('Page suivante', 'chassesautresor-com') . '"'
            . ($page >= $pages ? ' disabled' : '') . '>&rsaquo;</button></nav>';

        return $html;
    }

    private function pageButton(int $number, int $current): string
    {
        return '<button type="button" data-leaderboard-page="' . esc_attr((string) $number) . '"'
            . ($number === $current ? ' aria-current="page"' : '') . '>'
            . esc_html((string) $number) . '</button>';
    }

    private function attemptResultLabel(string $result, int $processed): string
    {
        return match ($result) {
            'bon' => __('Correcte', 'chassesautresor-com'),
            'faux' => __('Incorrecte', 'chassesautresor-com'),
            'variante' => __('Variante acceptée', 'chassesautresor-com'),
            'attente' => $processed === 0
                ? __('En attente', 'chassesautresor-com')
                : __('Traitée', 'chassesautresor-com'),
            default => __('Inconnue', 'chassesautresor-com'),
        };
    }

    private function formatDateTime(string $value): string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '';
        }

        return function_exists('wp_date')
            ? (string) wp_date('d/m/Y H:i', $timestamp)
            : date('d/m/Y H:i', $timestamp);
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
