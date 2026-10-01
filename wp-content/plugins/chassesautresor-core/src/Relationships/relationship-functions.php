<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

function cat_get_organizer_service(): OrganizerService
{
    global $wpdb;
    return CoreServiceFactory::organizer($wpdb);
}

function cat_get_relationship_service(): RelationshipService
{
    return new RelationshipService();
}

function get_organisateur_from_user($userId): ?int
{
    return cat_get_organizer_service()->findIdForUser((int) $userId);
}

function get_organisateur_chasse($huntId): ?int
{
    return cat_get_relationship_service()->normalizeId(get_field('organisateur_id', $huntId));
}

function get_organisateur_from_chasse($huntId): ?int
{
    return cat_get_relationship_service()->normalizeId(get_field('chasse_cache_organisateur', $huntId));
}

function get_organisateur_id_from_context(array $args = []): ?int
{
    if (isset($args['organisateur_id'])) {
        return (int) $args['organisateur_id'];
    }
    global $post;
    if ($post && get_post_type($post) === 'organisateur') {
        return (int) $post->ID;
    }
    return get_organisateur_from_user(get_current_user_id());
}

function utilisateur_est_organisateur_associe_a_chasse(int $userId, int $huntId): bool
{
    if ($userId <= 0 || $huntId <= 0) {
        return false;
    }
    $organizerId = get_organisateur_from_chasse($huntId);
    $users = $organizerId !== null ? get_field('utilisateurs_associes', $organizerId) : [];
    return is_array($users) && cat_get_organizer_service()->isUserAssociated($userId, $users);
}
