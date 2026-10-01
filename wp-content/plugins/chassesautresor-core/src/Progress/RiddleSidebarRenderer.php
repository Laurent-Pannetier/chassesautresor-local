<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Render the portable fragments returned by the riddle sidebar endpoints. */
final class RiddleSidebarRenderer
{
    private RiddleStatisticsService $statistics;
    private RiddleSidebarStatisticsService $sidebarStatistics;

    public function __construct(
        RiddleStatisticsService $statistics,
        RiddleSidebarStatisticsService $sidebarStatistics
    ) {
        $this->statistics = $statistics;
        $this->sidebarStatistics = $sidebarStatistics;
    }

    public function winners(int $riddleId, int $userId, int $page): string
    {
        $solvers = $this->statistics->listSolvers($riddleId, $this->excludedUserIds($riddleId));
        $total = count($solvers);
        $pages = max(1, (int) ceil($total / 10));
        $page = max(1, min($page, $pages));
        $solvers = array_slice($solvers, ($page - 1) * 10, 10);

        ob_start();
        ?>
        <h3><?= esc_html__('Gagnants', 'chassesautresor-com'); ?></h3>
        <div class="bloc-metas-inline bloc-metas-inline--compact">
            <div class="meta-etiquette">
                <span><?= esc_html__('Gagnants :', 'chassesautresor-com'); ?></span>
                <strong><?= esc_html($total); ?></strong>
            </div>
        </div>
        <?php if ($solvers !== []) : ?>
            <table class="stats-table compact">
                <thead><tr><th scope="col"></th>
                    <th scope="col"><?= esc_html__('Joueur', 'chassesautresor-com'); ?></th>
                    <th scope="col"><?= esc_html__('Date', 'chassesautresor-com'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($solvers as $index => $solver) : ?>
                    <tr<?= (int) $solver['user_id'] === $userId ? ' class="current-user"' : ''; ?>>
                        <td><?= esc_html(($page - 1) * 10 + $index + 1); ?></td>
                        <td><?= esc_html($solver['username']); ?></td>
                        <td><?= esc_html(mysql2date('d/m/y', $solver['date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?= $this->pager($page, $pages); ?>
        <?php else : ?>
            <p><?= esc_html__('Aucun gagnant pour le moment.', 'chassesautresor-com'); ?></p>
        <?php endif; ?>
        <?php
        return (string) ob_get_clean();
    }

    public function progression(int $huntId, int $riddleId, int $userId): string
    {
        $progression = $this->sidebarStatistics->progression($huntId, $userId);
        $resolution = $this->sidebarStatistics->resolution($riddleId);
        $html = '<h3>' . esc_html__('Statistiques', 'chassesautresor-com') . '</h3>';
        $html .= $this->metas($riddleId);
        $html .= $this->comparison(
            esc_html__('Progression', 'chassesautresor-com'),
            $progression['user'],
            $progression['avg'],
            'enigme-progression'
        );
        return $html . $this->singleRate(
            esc_html__('Résolution', 'chassesautresor-com'),
            $resolution,
            'enigme-resolution'
        );
    }

    public function metas(int $riddleId): string
    {
        $participants = $this->statistics->countEngagedPlayers(
            $riddleId,
            null,
            null,
            $this->excludedUserIds($riddleId)
        );
        $attempts = get_field('enigme_mode_validation', $riddleId) === 'aucune'
            ? null
            : $this->statistics->countAttempts($riddleId);

        $html = '<div class="bloc-metas-inline bloc-metas-inline--compact">';
        $html .= $this->meta(esc_html__('Nb joueurs :', 'chassesautresor-com'), $participants);
        if ($attempts !== null) {
            $html .= $this->meta(esc_html__('Nb tentatives :', 'chassesautresor-com'), $attempts);
        }
        return $html . '</div>';
    }

    /** @return int[] */
    private function excludedUserIds(int $riddleId): array
    {
        $relationships = new RelationshipService();
        $excluded = function_exists('get_users')
            ? (array) get_users(['role' => 'administrator', 'fields' => 'ids'])
            : [];
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId));
        $organizerId = $huntId === null
            ? null
            : $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId !== null) {
            $excluded = array_merge($excluded, (array) get_field('utilisateurs_associes', $organizerId));
        }
        return array_values(array_unique($relationships->normalizeIds($excluded)));
    }

    private function meta(string $label, int $value): string
    {
        return '<div class="meta-etiquette"><span>' . esc_html($label) . '</span><strong>'
            . esc_html($value) . '</strong></div>';
    }

    private function comparison(string $title, int $userRate, int $averageRate, string $class): string
    {
        return '<div class="' . esc_attr($class) . '"><p class="aside-subsection-title">'
            . esc_html($title) . '</p><div class="stats-bar-chart">'
            . $this->bar(esc_html__('Vous', 'chassesautresor-com'), $userRate, true)
            . $this->bar(esc_html__('Moyenne', 'chassesautresor-com'), $averageRate)
            . '</div></div>';
    }

    private function singleRate(string $title, int $rate, string $class): string
    {
        return '<div class="' . esc_attr($class) . '"><p class="aside-subsection-title">'
            . esc_html($title) . '</p><div class="stats-bar-chart">'
            . $this->bar($title, $rate) . '</div></div>';
    }

    private function bar(string $label, int $rate, bool $primary = false): string
    {
        $style = ($primary ? 'background-color:var(--color-primary);' : '') . 'width:' . $rate . '%;';
        $value = '<span class="bar-value">' . esc_html($rate) . '%</span>';
        $outside = '<span class="bar-value bar-value--outside" style="left:calc('
            . esc_attr($rate) . '% + 4px);">' . esc_html($rate) . '%</span>';

        return '<div class="bar-row"><span class="bar-label">' . esc_html($label)
            . '</span><div class="bar-wrapper"><div class="bar-fill" style="' . esc_attr($style) . '">'
            . ($rate >= 50 ? $value : '') . '</div>' . ($rate < 50 ? $outside : '') . '</div></div>';
    }

    private function pager(int $page, int $pages): string
    {
        if ($pages <= 1) {
            return '';
        }
        $options = '';
        for ($index = 1; $index <= $pages; $index++) {
            $selected = $index === $page ? ' selected="selected"' : '';
            $options .= '<option value="' . esc_attr($index) . '"' . $selected . '>' . esc_html($index) . '</option>';
        }
        return '<nav class="pager enigme-gagnants-pager" data-current="' . esc_attr($page)
            . '" data-total="' . esc_attr($pages) . '"><button type="button" class="etiquette pager-first"'
            . ' aria-label="' . esc_attr__('First page', 'chassesautresor-com') . '">&laquo;</button>'
            . '<button type="button" class="etiquette pager-prev" aria-label="'
            . esc_attr__('Previous page', 'chassesautresor-com') . '">&lsaquo;</button>'
            . '<span class="pager-info"><select class="etiquette pager-select" aria-label="'
            . esc_attr__('Go to page', 'chassesautresor-com') . '">' . $options . '</select> / '
            . esc_html($pages) . '</span><button type="button" class="etiquette pager-next" aria-label="'
            . esc_attr__('Next page', 'chassesautresor-com') . '">&rsaquo;</button>'
            . '<button type="button" class="etiquette pager-last" aria-label="'
            . esc_attr__('Last page', 'chassesautresor-com') . '">&raquo;</button></nav>';
    }
}
