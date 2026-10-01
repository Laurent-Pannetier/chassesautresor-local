<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Render the paginated participant table for hunt statistics. */
final class HuntStatisticsParticipantRenderer
{
    /**
     * @param array<int, array<string, mixed>> $participants
     * @param array{page:int, limit:int, order:string} $request
     */
    public function render(
        int $huntId,
        array $participants,
        array $request,
        int $total,
        int $pages,
        string $orderBy
    ): string {
        if ($participants === []) {
            return '';
        }

        $riddleCount = count(recuperer_ids_enigmes_pour_chasse($huntId));
        $participationIcon = $this->sortIcon('participation', $orderBy, $request['order']);
        $resolutionIcon = $this->sortIcon('resolution', $orderBy, $request['order']);
        ob_start();
        ?>
        <h3><?= esc_html__('Joueurs', 'chassesautresor-com'); ?></h3>
        <table class="stats-table compact">
            <colgroup><col style="width:20%"><col style="width:20%"><col style="width:20%">
                <col style="width:20%"><col style="width:20%"></colgroup>
            <thead><tr>
                <th scope="col"><?= esc_html__('Joueur', 'chassesautresor-com'); ?></th>
                <th scope="col"><?= esc_html__('Inscription', 'chassesautresor-com'); ?></th>
                <th scope="col"><?= esc_html__('Énigmes', 'chassesautresor-com'); ?></th>
                <?= $this->sortableHeader(
                    'participation',
                    __('Participation', 'chassesautresor-com'),
                    $participationIcon
                ); ?>
                <?= $this->sortableHeader(
                    'resolution',
                    __('Trouvées', 'chassesautresor-com'),
                    $resolutionIcon
                ); ?>
            </tr></thead>
            <tbody><?php foreach ($participants as $participant) : ?>
                <?= $this->row($participant, $riddleCount); ?>
            <?php endforeach; ?></tbody>
        </table>
        <?= \cta_render_pager($request['page'], $pages, 'chasse-participants-pager'); ?>
        <?php
        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $participant */
    private function row(array $participant, int $riddleCount): string
    {
        $labels = array_map(
            static fn (array $riddle): string => '<span class="etiquette">'
                . esc_html($riddle['title']) . '</span>',
            $participant['enigmes']
        );
        $registeredAt = $participant['date_inscription']
            ? mysql2date('d/m/Y H:i', $participant['date_inscription'])
            : '';
        ob_start();
        ?>
        <tr>
            <td><?= esc_html($participant['username']); ?></td>
            <td><?= esc_html($registeredAt); ?></td>
            <td><?= implode(' ', $labels); ?></td>
            <td><?= esc_html(sprintf('%d/%d', (int) $participant['nb_engagees'], $riddleCount)); ?></td>
            <td><?= esc_html(sprintf('%d/%d', (int) $participant['nb_resolues'], $riddleCount)); ?></td>
        </tr>
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

    private function sortableHeader(string $column, string $label, string $icon): string
    {
        return '<th scope="col" data-format="etiquette"><button class="sort" data-orderby="'
            . esc_attr($column) . '" aria-label="'
            . esc_attr(sprintf(__('Trier par %s', 'chassesautresor-com'), strtolower($label))) . '">'
            . esc_html($label) . '<i class="fa-solid ' . esc_attr($icon) . '"></i></button></th>';
    }
}
