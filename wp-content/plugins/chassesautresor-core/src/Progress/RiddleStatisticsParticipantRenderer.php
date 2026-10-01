<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Render the paginated participant table for riddle statistics. */
final class RiddleStatisticsParticipantRenderer
{
    /**
     * @param array<int, array<string, mixed>> $participants
     * @param array{page:int, limit:int, order:string} $request
     */
    public function render(
        int $riddleId,
        array $participants,
        array $request,
        int $total,
        int $pages,
        string $orderBy
    ): string {
        if ($participants === []) {
            return '<p>' . esc_html__('Aucun participant engagé.', 'chassesautresor-com') . '</p>';
        }

        $mode = get_field('enigme_mode_validation', $riddleId) ?? 'aucune';
        $dateIcon = $this->sortIcon('date', $orderBy, $request['order']);
        $attemptIcon = $this->sortIcon('tentatives', $orderBy, $request['order']);
        ob_start();
        ?>
        <h3><?= esc_html__('Liste participants', 'chassesautresor-com'); ?></h3>
        <table class="stats-table compact">
            <thead><tr>
                <th scope="col"><?= esc_html__('Nom', 'chassesautresor-com'); ?></th>
                <?= $this->sortableHeader('date', __('Engagement', 'chassesautresor-com'), $dateIcon); ?>
                <?php if ($mode !== 'aucune') : ?>
                    <?= $this->sortableHeader(
                        'tentatives',
                        __('Tentatives', 'chassesautresor-com'),
                        $attemptIcon,
                        ' data-format="etiquette"'
                    ); ?>
                <?php endif; ?>
                <th scope="col"><?= esc_html__('Trouvé', 'chassesautresor-com'); ?></th>
            </tr></thead>
            <tbody><?php foreach ($participants as $participant) : ?>
                <tr>
                    <td><?= esc_html($participant['username']); ?></td>
                    <td><?= esc_html(mysql2date('d/m/Y H:i', $participant['date_engagement'])); ?></td>
                    <?php if ($mode !== 'aucune') : ?>
                        <td><?= esc_html($participant['nb_tentatives']); ?></td>
                    <?php endif; ?>
                    <td><?= $participant['trouve'] ? '<i class="fa-solid fa-check fa-xl"></i>' : ''; ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table>
        <?= $this->pager($request['page'], $pages); ?>
        <?php
        return (string) ob_get_clean();
    }

    private function sortIcon(string $column, string $orderBy, string $order): string
    {
        if ($column !== $orderBy) {
            return 'fa-sort';
        }

        return strtoupper($order) === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
    }

    private function sortableHeader(string $column, string $label, string $icon, string $attributes = ''): string
    {
        return '<th scope="col"' . $attributes . '><button class="sort" data-orderby="'
            . esc_attr($column) . '" aria-label="'
            . esc_attr(sprintf(__('Trier par %s', 'chassesautresor-com'), strtolower($label))) . '">'
            . esc_html($label) . '<i class="fa-solid ' . esc_attr($icon) . '"></i></button></th>';
    }

    private function pager(int $page, int $pages): string
    {
        $html = '<div class="pager">';
        if ($page > 1) {
            $html .= '<button class="pager-first" aria-label="'
                . esc_attr__('Première page', 'chassesautresor-com')
                . '"><i class="fa-solid fa-angles-left"></i></button>';
            $html .= '<button class="pager-prev" aria-label="'
                . esc_attr__('Page précédente', 'chassesautresor-com')
                . '"><i class="fa-solid fa-angle-left"></i></button>';
        }
        $html .= '<span class="pager-info">' . esc_html($page) . ' / ' . esc_html($pages) . '</span>';
        if ($page < $pages) {
            $html .= '<button class="pager-next" aria-label="'
                . esc_attr__('Page suivante', 'chassesautresor-com')
                . '"><i class="fa-solid fa-angle-right"></i></button>';
            $html .= '<button class="pager-last" aria-label="'
                . esc_attr__('Dernière page', 'chassesautresor-com')
                . '"><i class="fa-solid fa-angles-right"></i></button>';
        }

        return $html . '</div>';
    }
}
