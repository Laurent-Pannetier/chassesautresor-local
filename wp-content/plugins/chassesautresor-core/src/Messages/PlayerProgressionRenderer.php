<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/** Render a player's progress and the hunt rankings shown on Classement. */
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
        echo '<section class="player-summary" aria-label="'
            . esc_attr(__('Votre progression', 'chassesautresor-com')) . '">';
        echo '<h2 class="screen-reader-text">'
            . esc_html(__('Votre progression', 'chassesautresor-com')) . '</h2>';
        $rank = (int) ($overview['rank'] ?? 0);
        $cards = [
            [__('Résolues', 'chassesautresor-com'), (string) (int) ($overview['resolved'] ?? 0)],
            [__('Tentatives', 'chassesautresor-com'), (string) (int) ($overview['attempts_total'] ?? 0)],
            [__('Classement', 'chassesautresor-com'), $rank > 0 ? $this->ordinal($rank) : '—'],
        ];
        foreach ($cards as [$label, $value]) {
            echo '<article class="player-summary__tile">';
            echo '<p class="team-stat__label">' . esc_html($label) . '</p>';
            echo '<p class="team-stat__value">' . esc_html($value) . '</p>';
            echo '</article>';
        }
        echo '</section>';
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
            echo '<div class="progression-table-scroll"><table class="progression-data-table'
                . ' progression-data-table--ranking progression-data-table--player-ranking">';
            echo '<thead><tr><th scope="col">' . esc_html__('Rang', 'chassesautresor-com') . '</th>';
            echo '<th scope="col">' . esc_html__('Joueur', 'chassesautresor-com') . '</th>';
            echo '<th scope="col">' . esc_html__('Équipe', 'chassesautresor-com') . '</th>';
            echo '<th scope="col">' . esc_html__('Énigmes résolues', 'chassesautresor-com')
                . '</th></tr></thead><tbody>';
            foreach ($rows as $index => $row) {
                $userId = (int) ($row['user_id'] ?? 0);
                $rank = (($page - 1) * 10) + $index + 1;
                $class = $userId === $currentUserId
                    ? ' class="is-current"'
                    : '';
                echo '<tr' . $class . '>';
                echo '<td class="progression-data-table__rank">' . esc_html((string) $rank) . '</td>';
                echo '<td><span class="progression-table-identity">' . $this->avatar($userId, 40);
                echo '<strong>' . esc_html((string) ($row['username'] ?? '')) . '</strong>';
                echo '<small>' . esc_html($this->teamLabel((string) ($row['team_name'] ?? '')))
                    . '</small></span></td>';
                echo '<td class="progression-data-table__team">'
                    . esc_html($this->teamLabel((string) ($row['team_name'] ?? ''))) . '</td>';
                $resolved = (int) ($row['resolved'] ?? 0);
                echo '<td class="progression-data-table__score">' . esc_html((string) $resolved) . '</td></tr>';
            }
            echo '</tbody></table></div>';
        }
        if ($position > 0 && ($position < (($page - 1) * 10 + 1) || $position > $page * 10)) {
            echo '<p class="progression-leaderboard__position">' . esc_html(sprintf(
                /* translators: %s: current player's rank, such as 1er or 2e */
                __('Votre position : %s', 'chassesautresor-com'),
                $this->ordinal($position)
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
        if ($riddles === []) {
            echo '<h2 class="progression-riddles__title">'
                . esc_html(__('Énigmes', 'chassesautresor-com')) . '</h2>';
            echo '<p class="progression-riddles__empty">'
                . esc_html(__('Cette chasse ne contient pas encore d’énigme.', 'chassesautresor-com'))
                . '</p></section>';
            return;
        }

        echo '<div class="progression-table-card progression-riddles__table-wrap">';
        echo '<h2 class="progression-riddles__title">'
            . esc_html(__('Énigmes', 'chassesautresor-com')) . '</h2>';
        echo '<table class="progression-data-table progression-data-table--riddles">';
        echo '<thead><tr><th scope="col">' . esc_html__('Énigme', 'chassesautresor-com') . '</th>';
        echo '<th scope="col">' . esc_html__('Statut', 'chassesautresor-com') . '</th>';
        echo '<th scope="col">' . esc_html__('Étapes franchies', 'chassesautresor-com') . '</th>';
        echo '<th scope="col">' . esc_html__('Tentatives', 'chassesautresor-com') . '</th>';
        echo '<th scope="col">' . esc_html__('Dernière activité', 'chassesautresor-com') . '</th>';
        echo '<th scope="col">' . esc_html__('Soumissions', 'chassesautresor-com') . '</th>';
        echo '<th scope="col">' . esc_html__('Classement', 'chassesautresor-com') . '</th></tr></thead>';
        foreach ($riddles as $riddle) {
            $this->renderRiddle($riddle);
        }
        echo '</table></div></section>';
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
            ? $lastActivity
            : __('Pas encore d’activité', 'chassesautresor-com');
        $attempts = (int) ($riddle['attempts'] ?? 0);

        echo '<tbody class="progression-riddle">';
        echo '<tr class="progression-riddle__row">';
        echo '<th scope="row"><span class="progression-riddle__identity">';
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
        echo '<span class="progression-riddle__title">';
        if ($url !== '') {
            echo '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>';
        } else {
            echo esc_html($title);
        }
        echo '</span></span></th>';
        echo '<td data-label="' . esc_attr__('Statut', 'chassesautresor-com') . '"><span class="'
            . esc_attr('progression-riddle__status progression-riddle__status--' . $statusClass) . '">'
            . esc_html($this->statusLabel($status)) . '</span></td>';
        echo '<td data-label="' . esc_attr__('Étapes franchies', 'chassesautresor-com') . '">';
        if ($stepsTotal > 0) {
            echo '<strong>' . esc_html((string) (int) ($riddle['steps_done'] ?? 0)) . '</strong>';
        } else {
            echo '<span aria-hidden="true">—</span><span class="screen-reader-text">'
                . esc_html__('Sans étape', 'chassesautresor-com') . '</span>';
        }
        echo '</td>';
        echo '<td data-label="' . esc_attr__('Tentatives', 'chassesautresor-com') . '"><strong>'
            . esc_html((string) $attempts) . '</strong></td>';
        echo '<td data-label="' . esc_attr__('Dernière activité', 'chassesautresor-com')
            . '" class="progression-riddle__activity">' . esc_html($activityLabel) . '</td>';
        echo '<td class="progression-riddle__submission-cell">';
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
        } else {
            echo '<span aria-hidden="true">—</span>';
        }
        echo '</td>';
        echo '<td data-label="' . esc_attr__('Classement', 'chassesautresor-com')
            . '" class="progression-riddle__rank">' . $this->rankLabel((int) ($riddle['rank'] ?? 0))
            . '</td></tr>';
        echo '<tr class="progression-riddle__attempts-row" data-riddle-attempts-row hidden><td colspan="7">';
        echo '<div class="progression-riddle__attempts" data-riddle-attempts-panel></div>';
        echo '</td></tr></tbody>';
    }

    /**
     * @param array<string, mixed> $view
     * @param array<int, array{id:int,title:string,url:string}> $hunts
     * @param array<string, string> $ajax
     */
    public function renderRanking(
        array $view,
        array $hunts = [],
        int $currentHuntId = 0,
        array $ajax = []
    ): string {
        ob_start();
        echo '<section class="player-ranking"';
        if ($ajax !== []) {
            echo ' data-player-ranking data-ajax-url="' . esc_url((string) ($ajax['url'] ?? '')) . '"';
            echo ' data-nonce="' . esc_attr((string) ($ajax['nonce'] ?? '')) . '"';
            echo ' data-hunt-id="' . esc_attr((string) $currentHuntId) . '"';
            echo ' data-error-message="'
                . esc_attr__('Impossible de charger ces données.', 'chassesautresor-com') . '"';
        }
        echo '>';
        $this->renderHuntSwitcher($hunts, $currentHuntId);
        $board = (string) ($view['board'] ?? 'joueurs');
        $this->renderBoardSwitcher((array) ($view['views'] ?? []), $board);
        if ($board === 'equipes') {
            $this->renderTeamBoard($view);
        } else {
            $this->renderPlayerBoards($view);
        }
        echo '</section>';

        return trim((string) ob_get_clean());
    }

    /** @param array<int, array<string, mixed>> $views */
    private function renderBoardSwitcher(array $views, string $current): void
    {
        if (count($views) < 2) {
            return;
        }

        echo '<nav class="progression-hunts player-ranking__boards" aria-label="'
            . esc_attr(__('Classement affiché', 'chassesautresor-com')) . '">';
        foreach ($views as $item) {
            $id = (string) ($item['id'] ?? '');
            $title = trim((string) ($item['title'] ?? ''));
            if ($id === '' || $title === '') {
                continue;
            }
            $active = $id === $current ? ' aria-current="page"' : '';
            echo '<a class="progression-hunts__link" href="'
                . esc_url((string) ($item['url'] ?? '')) . '"' . $active . '>'
                . esc_html($title) . '</a>';
        }
        echo '</nav>';
    }

    /** @param array<string, mixed> $view */
    private function renderPlayerBoards(array $view): void
    {
        echo '<section class="progression-leaderboard progression-table-card player-ranking__board"'
            . ' aria-labelledby="player-ranking-title">';
        echo '<header class="player-ranking__heading">';
        echo '<h2 id="player-ranking-title">'
            . esc_html__('Classement de la chasse', 'chassesautresor-com') . '</h2>';
        echo '</header>';
        echo '<div data-progression-leaderboard>';
        echo $this->leaderboard((array) ($view['leaderboard'] ?? []));
        echo '</div></section>';
        foreach ((array) ($view['riddles'] ?? []) as $riddle) {
            $riddleId = (int) ($riddle['id'] ?? 0);
            $title = trim((string) ($riddle['title'] ?? ''));
            if ($title === '') {
                $title = __('Énigme', 'chassesautresor-com');
            }
            $headingId = 'player-ranking-riddle-' . $riddleId;
            echo '<section class="progression-leaderboard progression-table-card player-ranking__board'
                . ' player-ranking__board--riddle" aria-labelledby="' . esc_attr($headingId) . '">';
            echo '<header class="player-ranking__heading player-ranking__heading--riddle">';
            echo '<h2 id="' . esc_attr($headingId) . '">';
            echo '<span class="player-ranking__kicker">' . esc_html__('Énigme', 'chassesautresor-com')
                . '</span>';
            echo esc_html($title);
            echo '</h2></header>';
            echo '<div data-riddle-ranking data-riddle-id="' . esc_attr((string) $riddleId) . '">';
            echo $this->riddleRanking((array) ($riddle['ranking'] ?? []));
            echo '</div></section>';
        }
    }

    /** @param array<string, mixed> $view */
    private function renderTeamBoard(array $view): void
    {
        $ajax = is_array($view['team_ajax'] ?? null) ? $view['team_ajax'] : [];
        echo '<section class="progression-leaderboard progression-table-card player-ranking__board'
            . ' equipe-ranking" aria-labelledby="team-ranking-title"';
        if ($ajax !== []) {
            echo ' data-team-performance data-ajax-url="' . esc_url((string) ($ajax['url'] ?? '')) . '"';
            echo ' data-nonce="' . esc_attr((string) ($ajax['nonce'] ?? '')) . '"';
            echo ' data-hunt-id="' . esc_attr((string) (int) ($view['hunt_id'] ?? 0)) . '"';
            echo ' data-error-message="'
                . esc_attr__('Impossible de charger le classement.', 'chassesautresor-com') . '"';
        }
        echo '>';
        echo '<header class="player-ranking__heading">';
        echo '<h2 id="team-ranking-title">'
            . esc_html__('Classement des équipes', 'chassesautresor-com') . '</h2>';
        echo '</header>';
        echo '<div data-team-ranking>';
        echo (string) ($view['team_ranking_html'] ?? '');
        echo '</div></section>';
    }

    /** @param array<string, mixed> $view */
    public function riddleRanking(array $view): string
    {
        $rows = array_values((array) ($view['rows'] ?? []));
        $page = max(1, (int) ($view['page'] ?? 1));
        $pages = max(1, (int) ($view['pages'] ?? 1));
        $currentUserId = (int) ($view['current_user_id'] ?? 0);
        $viewer = is_array($view['viewer'] ?? null) ? $view['viewer'] : [];
        $viewerRank = (int) ($viewer['rank'] ?? 0);
        $fold = $pages > 1;
        $pageStart = (($page - 1) * 10) + 1;
        $pageEnd = $page * 10;
        ob_start();
        if ($rows === []) {
            echo '<p class="progression-leaderboard__empty">'
                . esc_html__('Pas encore de classement.', 'chassesautresor-com') . '</p>';
        } else {
            if ($fold) {
                echo '<div class="progression-ranking-fold" data-ranking-fold data-page="'
                    . esc_attr((string) $page) . '">';
            }
            echo '<div class="progression-table-scroll"><table class="progression-data-table'
                . ' progression-data-table--ranking progression-data-table--player-ranking'
                . ' progression-data-table--riddle-ranking">';
            echo '<thead><tr><th scope="col">' . esc_html__('Rang', 'chassesautresor-com') . '</th>';
            echo '<th scope="col">' . esc_html__('Joueur', 'chassesautresor-com') . '</th>';
            echo '<th scope="col">' . esc_html__('Équipe', 'chassesautresor-com') . '</th>';
            echo '<th scope="col">' . esc_html__('Avancement', 'chassesautresor-com')
                . '</th></tr></thead><tbody>';
            foreach ($rows as $index => $row) {
                $rank = $pageStart + $index;
                $extra = $fold && $page === 1 && $index >= 3;
                $this->riddleRankingRow($row, $rank, $currentUserId, $extra);
            }
            if ($fold && $viewerRank > 3) {
                $listed = $viewerRank >= $pageStart && $viewerRank <= $pageEnd;
                $this->riddleRankingRow(
                    $viewer,
                    $viewerRank,
                    $currentUserId,
                    false,
                    true,
                    $listed
                );
            }
            echo '</tbody></table></div>';
            if ($fold) {
                echo '<div class="progression-ranking-fold__actions progression-ranking-fold__expand">';
                echo '<button type="button" data-ranking-expand aria-expanded="false">'
                    . esc_html__('Voir plus', 'chassesautresor-com') . '</button></div>';
                echo '<div class="progression-ranking-fold__actions progression-ranking-fold__collapse">';
                echo '<button type="button" data-ranking-collapse aria-expanded="true">'
                    . esc_html__('Réduire', 'chassesautresor-com') . '</button></div>';
            }
            echo $this->numberedPager(
                $page,
                $pages,
                'data-riddle-ranking-page',
                $fold ? 'progression-ranking-fold__pager' : ''
            );
            if ($fold) {
                echo '</div>';
            }
        }

        return trim((string) ob_get_clean());
    }

    /** @param array<string, mixed> $row */
    private function riddleRankingRow(
        array $row,
        int $rank,
        int $currentUserId,
        bool $extra,
        bool $pin = false,
        bool $listed = false
    ): void {
        $userId = (int) ($row['user_id'] ?? 0);
        $classes = [];
        if ($userId === $currentUserId && $currentUserId > 0) {
            $classes[] = 'is-current';
        }
        if ($extra) {
            $classes[] = 'progression-ranking-extra';
        }
        if ($pin) {
            $classes[] = 'progression-ranking-pin';
        }
        $class = $classes === [] ? '' : ' class="' . esc_attr(implode(' ', $classes)) . '"';
        $listedAttr = $pin
            ? ' data-pin-listed="' . ($listed ? '1' : '0') . '"'
            : '';
        $solved = !empty($row['solved']);
        $badgeClass = $solved
            ? 'progression-riddle__status progression-riddle__status--resolue'
            : 'progression-riddle__status progression-riddle__status--en_cours';
        echo '<tr' . $class . $listedAttr . '>';
        echo '<td class="progression-data-table__rank">' . esc_html((string) $rank) . '</td>';
        echo '<td><span class="progression-table-identity">' . $this->avatar($userId, 40);
        echo '<strong>' . esc_html((string) ($row['username'] ?? '')) . '</strong>';
        echo '<small>' . esc_html($this->teamLabel((string) ($row['team_name'] ?? '')))
            . '</small></span></td>';
        echo '<td class="progression-data-table__team">'
            . esc_html($this->teamLabel((string) ($row['team_name'] ?? ''))) . '</td>';
        echo '<td><span class="' . esc_attr($badgeClass) . '">' . esc_html(
            $solved
                ? __('Résolue', 'chassesautresor-com')
                : __('En progression', 'chassesautresor-com')
        ) . '</span></td></tr>';
    }

    private function rankLabel(int $rank): string
    {
        if ($rank <= 0) {
            return '<span aria-hidden="true">—</span><span class="screen-reader-text">'
                . esc_html__('Pas de classement', 'chassesautresor-com') . '</span>';
        }

        return esc_html($this->ordinal($rank));
    }

    private function ordinal(int $rank): string
    {
        if ($rank === 1) {
            return __('1er', 'chassesautresor-com');
        }

        return sprintf(
            /* translators: %d: rank from 2 upward */
            __('%de', 'chassesautresor-com'),
            $rank
        );
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

    private function numberedPager(
        int $page,
        int $pages,
        string $attribute = 'data-leaderboard-page',
        string $extraClass = ''
    ): string {
        if ($pages <= 1) {
            return '';
        }

        $class = trim('progression-leaderboard__pager ' . $extraClass);
        $html = '<nav class="' . esc_attr($class) . '" aria-label="'
            . esc_attr__('Pages du classement', 'chassesautresor-com') . '">';
        $html .= '<button type="button" ' . $attribute . '="' . esc_attr((string) max(1, $page - 1))
            . '" aria-label="' . esc_attr__('Page précédente', 'chassesautresor-com') . '"'
            . ($page <= 1 ? ' disabled' : '') . '>&lsaquo;</button>';
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        if ($start > 1) {
            $html .= $this->pageButton(1, $page, $attribute);
            if ($start > 2) {
                $html .= '<span aria-hidden="true">…</span>';
            }
        }
        for ($number = $start; $number <= $end; $number++) {
            $html .= $this->pageButton($number, $page, $attribute);
        }
        if ($end < $pages) {
            if ($end < $pages - 1) {
                $html .= '<span aria-hidden="true">…</span>';
            }
            $html .= $this->pageButton($pages, $page, $attribute);
        }
        $html .= '<button type="button" ' . $attribute . '="' . esc_attr((string) min($pages, $page + 1))
            . '" aria-label="' . esc_attr__('Page suivante', 'chassesautresor-com') . '"'
            . ($page >= $pages ? ' disabled' : '') . '>&rsaquo;</button></nav>';

        return $html;
    }

    private function pageButton(int $number, int $current, string $attribute): string
    {
        return '<button type="button" ' . $attribute . '="' . esc_attr((string) $number) . '"'
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
