<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/** Render portable rows for conversion request history. */
final class ConversionHistoryRenderer
{
    /** @param array<int, array<string, mixed>> $requests */
    public function rows(array $requests, bool $isAdministrator): string
    {
        ob_start();
        foreach ($requests as $request) {
            $points = abs((int) $request['points']);
            $userName = $isAdministrator ? $this->userName((int) $request['user_id']) : '';
            ?>
            <tr>
                <td><?= esc_html(date_i18n('d/m/Y à H:i', strtotime($request['request_date']))); ?></td>
                <?php if ($isAdministrator) : ?>
                    <td><?= esc_html($userName); ?></td>
                <?php endif; ?>
                <td><?= esc_html($request['amount_eur']); ?> €</td>
                <td><span class="etiquette etiquette-grande"><?= esc_html($points); ?></span></td>
                <td>
                    <span class="etiquette">
                        <?= esc_html($this->status((string) $request['request_status'])); ?>
                    </span>
                </td>
            </tr>
            <?php
        }

        return (string) ob_get_clean();
    }

    private function status(string $status): string
    {
        $labels = [
            'paid' => '✅ ' . __('Réglé', 'chassesautresor-com'),
            'cancelled' => '❌ ' . __('Annulé', 'chassesautresor-com'),
            'refused' => '🚫 ' . __('Refusé', 'chassesautresor-com'),
        ];

        return $labels[$status] ?? '🟡 ' . __('En attente', 'chassesautresor-com');
    }

    private function userName(int $userId): string
    {
        $user = get_userdata($userId);

        return $user
            ? (string) $user->display_name
            : sprintf(__('ID %d', 'chassesautresor-com'), $userId);
    }
}
