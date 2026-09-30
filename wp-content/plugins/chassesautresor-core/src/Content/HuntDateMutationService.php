<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use DateTimeInterface;

/**
 * Validate and persist the editable scheduling fields of a hunt.
 */
class HuntDateMutationService {
    /**
     * @param callable(string, array<int, string>): ?DateTimeInterface $parseDate
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(int, string, int): mixed $updateMeta
     * @param callable(int, string): mixed $getMeta
     * @return array{error:?string,data:?array{date_debut:string,date_fin:string,illimitee:int}}
     */
    public function apply(
        int $huntId,
        string $startDate,
        string $endDate,
        bool $unlimited,
        bool $deferredStart,
        callable $parseDate,
        callable $updateField,
        callable $updateMeta,
        callable $getMeta
    ): array {
        $start = $parseDate($startDate, [
            'Y-m-d\TH:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
        ]);
        if (!$start instanceof DateTimeInterface) {
            return ['error' => 'format_debut_invalide', 'data' => null];
        }

        $end = null;
        if (!$unlimited) {
            $end = $parseDate($endDate, ['Y-m-d', 'Y-m-d H:i:s', 'Y-m-d\TH:i']);
            if (!$end instanceof DateTimeInterface) {
                return ['error' => 'format_fin_invalide', 'data' => null];
            }

            $end = $end->setTime(0, 0, 0);
            if ($end->getTimestamp() <= $start->getTimestamp()) {
                return ['error' => 'date_fin_avant_debut', 'data' => null];
            }
        }

        $savedStart = $start->format('Y-m-d H:i:s');
        $savedEnd = $end instanceof DateTimeInterface ? $end->format('Y-m-d') : '';
        $startUpdated = $updateField('chasse_infos_date_debut', $savedStart, $huntId);
        $unlimitedUpdated = $updateField('chasse_infos_duree_illimitee', $unlimited ? 1 : 0, $huntId);
        $endUpdated = $unlimited
            ? true
            : $updateField('chasse_infos_date_fin', $savedEnd, $huntId);
        $updateMeta($huntId, 'chasse_infos_date_debut_differee', $deferredStart ? 1 : 0);

        $startMatches = (string) $getMeta($huntId, 'chasse_infos_date_debut') === $savedStart;
        $endMatches = $unlimited
            || (string) $getMeta($huntId, 'chasse_infos_date_fin') === $savedEnd;
        $unlimitedMatches = (int) $getMeta($huntId, 'chasse_infos_duree_illimitee') === ($unlimited ? 1 : 0);

        if (($startUpdated === false && !$startMatches)
            || ($unlimitedUpdated === false && !$unlimitedMatches)
            || ($endUpdated === false && !$endMatches)
        ) {
            return ['error' => 'echec_mise_a_jour', 'data' => null];
        }

        return [
            'error' => null,
            'data' => [
                'date_debut' => $savedStart,
                'date_fin' => $savedEnd,
                'illimitee' => $unlimited ? 1 : 0,
            ],
        ];
    }
}
