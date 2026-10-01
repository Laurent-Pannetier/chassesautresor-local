<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

final class ManualPointsAdjustmentService
{
    /** @return array{delta:int,reason:string,error:string} */
    public function prepare(string $action, int $amount, int $balance): array
    {
        if ($amount <= 0 || !in_array($action, ['ajouter', 'retirer'], true)) {
            return ['delta' => 0, 'reason' => '', 'error' => 'invalid'];
        }
        if ($action === 'retirer' && $amount > $balance) {
            return ['delta' => 0, 'reason' => '', 'error' => 'balance'];
        }

        $adding = $action === 'ajouter';

        return [
            'delta' => $adding ? $amount : -$amount,
            'reason' => sprintf(
                $adding
                    ? __('Ajout manuel de %d points', 'chassesautresor-com')
                    : __('Retrait manuel de %d points', 'chassesautresor-com'),
                $amount
            ),
            'error' => '',
        ];
    }
}
