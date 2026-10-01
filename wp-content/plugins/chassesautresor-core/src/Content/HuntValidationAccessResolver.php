<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Resolve the complete WordPress context required by the hunt validation policy. */
final class HuntValidationAccessResolver
{
    public function canRequest(int $huntId, int $userId): bool
    {
        if ($huntId <= 0 || $userId <= 0 || get_post_type($huntId) !== 'chasse') {
            return false;
        }

        $user = get_userdata($userId);
        $roles = $user && is_array($user->roles) ? $user->roles : [];
        $organizerRoles = array_filter([
            defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur',
            defined('ROLE_ORGANISATEUR_CREATION') ? ROLE_ORGANISATEUR_CREATION : 'organisateur_creation',
        ]);
        if (array_intersect($roles, $organizerRoles) === []) {
            return false;
        }

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId === null) {
            return false;
        }

        $organizerUsers = $relationships->normalizeIds(
            (array) get_field('utilisateurs_associes', $organizerId)
        );
        if (!in_array($userId, $organizerUsers, true)) {
            return false;
        }

        $riddleIds = get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId));
        $riddles = [];
        foreach ($riddleIds as $riddleId) {
            $riddles[] = [
                'system_status' => (string) get_field('enigme_cache_etat_systeme', (int) $riddleId),
                'is_complete' => (bool) get_field('enigme_cache_complet', (int) $riddleId),
            ];
        }

        return (new HuntValidationService())->canRequestValidation(
            true,
            true,
            (bool) get_field('organisateur_cache_complet', $organizerId),
            (bool) get_field('chasse_cache_complet', $huntId),
            (string) get_post_status($huntId),
            (string) get_field('chasse_cache_statut_validation', $huntId),
            (string) get_field('chasse_cache_statut', $huntId),
            $riddles
        );
    }
}
