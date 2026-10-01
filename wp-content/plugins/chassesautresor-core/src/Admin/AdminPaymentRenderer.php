<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use ChassesAuTresor\Core\Relationships\OrganizerRepository;

/** Render the payment requests managed by administrators. */
final class AdminPaymentRenderer
{
    private OrganizerRepository $organizers;

    public function __construct(OrganizerRepository $organizers)
    {
        $this->organizers = $organizers;
    }

    /** @param array<int, array<string, mixed>> $requests */
    public function table(array $requests): string
    {
        ob_start();
        ?>
        <table class="widefat fixed">
            <thead><tr>
                <th><?= esc_html__('Organisateur', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('Montant / Points', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('Date demande', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('IBAN / BIC', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('Statut', 'chassesautresor-com'); ?></th>
                <th><?= esc_html__('Action', 'chassesautresor-com'); ?></th>
            </tr></thead>
            <tbody><?php foreach ($requests as $request) : ?>
                <?= $this->row($request); ?>
            <?php endforeach; ?></tbody>
        </table>
        <?php
        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $request */
    private function row(array $request): string
    {
        $userId = (int) $request['user_id'];
        $user = get_userdata($userId);
        $organizerId = $this->organizers->findIdForUser($userId) ?? 0;
        $iban = $organizerId > 0 ? get_field('iban', $organizerId) : '';
        $bic = $organizerId > 0 ? get_field('bic', $organizerId) : '';
        if ($organizerId > 0 && (!$iban || !$bic)) {
            $iban = get_field('gagnez_de_largent_iban', $organizerId);
            $bic = get_field('gagnez_de_largent_bic', $organizerId);
        }
        $iban = $iban ?: __('Non renseigné', 'chassesautresor-com');
        ob_start();
        ?>
        <tr>
            <td><?= esc_html($user->display_name ?? ''); ?></td>
            <td><?= esc_html($request['amount_eur']); ?> €<br>
                <small>(<?= esc_html(abs((int) $request['points'])); ?> points)</small></td>
            <td><?= esc_html(date_i18n('Y-m-d H:i', strtotime($request['request_date']))); ?></td>
            <td><strong><?= esc_html($iban); ?></strong><br><small><?= esc_html($bic); ?></small></td>
            <td class="col-status"><?= esc_html($this->status((string) $request['request_status'])); ?></td>
            <td><?= $this->action($request); ?></td>
        </tr>
        <?php
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

    /** @param array<string, mixed> $request */
    private function action(array $request): string
    {
        if ($request['request_status'] !== 'pending') {
            return '-';
        }

        return '<form class="js-update-request" data-id="' . esc_attr($request['id']) . '">'
            . '<select name="statut"><option value="regle" selected>'
            . esc_html__('Régler', 'chassesautresor-com') . '</option><option value="annule">'
            . esc_html__('Annuler', 'chassesautresor-com') . '</option><option value="refuse">'
            . esc_html__('Refuser', 'chassesautresor-com') . '</option></select>'
            . '<button type="submit" class="button">OK</button></form>';
    }
}
