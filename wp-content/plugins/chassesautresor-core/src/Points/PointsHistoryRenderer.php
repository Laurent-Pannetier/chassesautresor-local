<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/** Render portable rows for the points history. */
final class PointsHistoryRenderer
{
    /** @param array<int, array<string, mixed>> $operations */
    public function rows(array $operations): string
    {
        ob_start();
        foreach ($operations as $operation) {
            $variation = (int) $operation['points'];
            $variationLabel = $variation > 0 ? '+' . $variation : (string) $variation;
            $date = !empty($operation['request_date'])
                ? mysql2date('d/m/Y', $operation['request_date'])
                : '';
            ?>
            <tr>
                <td><?= esc_html($operation['id']); ?></td>
                <td><?= esc_html($date); ?></td>
                <td><span class="etiquette"><?= esc_html($operation['origin_type']); ?></span></td>
                <td><?= wp_kses_post($this->formatReason($operation)); ?></td>
                <td><span class="etiquette etiquette-grande"><?= esc_html($variationLabel); ?></span></td>
                <td><span class="etiquette etiquette-grande"><?= esc_html($operation['balance']); ?></span></td>
            </tr>
            <?php
        }

        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $operation */
    public function formatReason(array $operation): string
    {
        $reason = (string) ($operation['reason'] ?? '');
        $originId = isset($operation['origin_id']) ? (int) $operation['origin_id'] : 0;
        $originType = (string) ($operation['origin_type'] ?? '');
        if ($originId <= 0 || !in_array($originType, ['chasse', 'tentative', 'indice', 'enigme'], true)) {
            return $reason;
        }

        $title = get_the_title($originId);
        $link = get_permalink($originId);
        if (!$title || !$link) {
            return $reason;
        }

        $replacement = sprintf('<a href="%s">%s</a>', esc_url($link), esc_html($title));
        return str_replace('#' . $originId, $replacement, $reason);
    }
}
