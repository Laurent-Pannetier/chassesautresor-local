<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Synchronize the publication plan of a solution with its hunt state.
 */
class SolutionPublicationPlanner {
    public const INVALID_STATE = 'INVALIDE';
    public const PUBLICATION_HOOK = 'publier_solution_programmee';

    /**
     * @param mixed $linkedHunt
     * @param mixed $riddleHunt
     */
    public static function resolveHuntId(string $targetType, $linkedHunt, $riddleHunt): ?int {
        return (new RelationshipService())->resolveTargetHuntId(
            $targetType,
            $linkedHunt,
            $riddleHunt
        );
    }

    public static function plan(int $solutionId): void {
        if (get_post_type($solutionId) !== 'solution') {
            return;
        }

        $relationshipService = new RelationshipService();
        $targetType = (string) get_field('solution_cible_type', $solutionId);
        $linkedHunt = get_field('solution_chasse_linked', $solutionId);
        $riddleHunt = null;

        if ($targetType === 'enigme') {
            $riddleId = $relationshipService->normalizeId(
                get_field('solution_enigme_linked', $solutionId)
            );
            $riddleHunt = $riddleId ? get_field('enigme_chasse_associee', $riddleId) : null;
            $riddleHunt = $relationshipService->normalizeId($riddleHunt) !== null
                ? $riddleHunt
                : $linkedHunt;
        }
        $huntId = self::resolveHuntId($targetType, $linkedHunt, $riddleHunt);

        if ($huntId === null) {
            update_field('solution_cache_etat_systeme', self::INVALID_STATE, $solutionId);
            return;
        }

        $availability = get_field('solution_disponibilite', $solutionId) ?: 'fin_chasse';
        $publicationTime = get_field('solution_heure_publication', $solutionId) ?: '00:00';
        $plan = (new SolutionAvailabilityService())->getPublicationPlan(
            (string) get_field('chasse_cache_statut', $huntId),
            (string) $availability,
            (int) get_field('solution_decalage_jours', $solutionId),
            (string) $publicationTime,
            (int) current_time('timestamp'),
            self::winnerAnnounced($huntId)
        );

        wp_clear_scheduled_hook(self::PUBLICATION_HOOK, [$solutionId]);

        if ($plan['state'] === SolutionPublicationService::AVAILABLE_STATE) {
            SolutionPublicationService::makeAccessible($solutionId);
            return;
        }

        update_field('solution_cache_etat_systeme', $plan['state'], $solutionId);
        if ($plan['target_timestamp'] === null) {
            delete_post_meta($solutionId, 'solution_date_disponibilite');
            return;
        }

        update_post_meta(
            $solutionId,
            'solution_date_disponibilite',
            gmdate('Y-m-d H:i:s', $plan['target_timestamp'])
        );
        wp_schedule_single_event(
            $plan['target_timestamp'],
            self::PUBLICATION_HOOK,
            [$solutionId]
        );
    }

    /**
     * Publish solutions still waiting for the hunt end once a winner is announced
     * or the hunt is actually finished. Already scheduled solutions are left as-is.
     */
    public static function releaseWaiting(int $huntId): void
    {
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            return;
        }
        $finished = (string) get_field('chasse_cache_statut', $huntId) === 'termine';
        if (!$finished && !self::winnerAnnounced($huntId)) {
            return;
        }

        foreach (self::solutionIds($huntId) as $solutionId) {
            $state = (string) get_field('solution_cache_etat_systeme', $solutionId);
            if (!in_array($state, ['', 'FIN_CHASSE', 'FIN_CHASSE_DIFFERE'], true)) {
                continue;
            }
            self::plan($solutionId);
        }
    }

    public static function winnerAnnounced(int $huntId): bool
    {
        $value = get_field('chasse_cache_date_decouverte', $huntId);
        if ($value instanceof \DateTimeInterface) {
            return true;
        }
        if (is_array($value)) {
            $value = $value['date'] ?? '';
        }

        return trim((string) $value) !== '';
    }

    /** @return int[] */
    private static function solutionIds(int $huntId): array
    {
        $ids = self::idsForTarget($huntId, 'chasse');
        $riddleIds = array_map(
            'intval',
            (array) get_posts(
                (new \ChassesAuTresor\Core\Relationships\HuntRiddleQueryService())
                    ->getRiddleIdsQueryArgs($huntId)
            )
        );
        foreach ($riddleIds as $riddleId) {
            if ($riddleId > 0) {
                $ids = array_merge($ids, self::idsForTarget($riddleId, 'enigme'));
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** @return int[] */
    private static function idsForTarget(int $targetId, string $targetType): array
    {
        $query = (new SolutionQueryService())->getExistingSolutionIdsQueryArgs($targetId, $targetType);
        if ($query === []) {
            return [];
        }

        return array_map('intval', (array) get_posts($query));
    }
}
