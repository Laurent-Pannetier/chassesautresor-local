<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

final class HuntModerationMutationService
{
    /**
     * @param array{hunt_status:string,validation_status:string,riddle_status:string,riddle_state:?string} $plan
     * @param int[] $riddleIds
     * @param array<string, mixed> $cache
     */
    public function apply(
        int $huntId,
        array $riddleIds,
        array $cache,
        array $plan,
        callable $updatePost,
        callable $updateField,
        callable $refreshHunt,
        callable $refreshRiddle
    ): void {
        $updatePost(['ID' => $huntId, 'post_status' => $plan['hunt_status']]);
        $cache['chasse_cache_statut_validation'] = $plan['validation_status'];
        $updateField('champs_caches', $cache, $huntId);
        $updateField('chasse_cache_statut_validation', $plan['validation_status'], $huntId);
        $refreshHunt($huntId);

        foreach ($riddleIds as $riddleId) {
            $updatePost(['ID' => $riddleId, 'post_status' => $plan['riddle_status']]);
            if ($plan['riddle_state'] !== null) {
                $updateField('enigme_cache_etat_systeme', $plan['riddle_state'], $riddleId);
            } elseif ($plan['validation_status'] === 'valide') {
                $refreshRiddle($riddleId);
            }
        }
    }
}
