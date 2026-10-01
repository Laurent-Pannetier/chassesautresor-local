<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ContentCreationService;
use ChassesAuTresor\Core\Content\ContentModificationService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Determine whether the current user can create application content.
 */
function utilisateur_peut_creer_post($postType, $huntId = null): bool
{
    global $wpdb;

    $postType = (string) $postType;
    $authenticated = is_user_logged_in();
    $administrator = $authenticated && current_user_can('manage_options');
    $userId = $authenticated && !$administrator ? get_current_user_id() : 0;
    $roles = $userId > 0 ? (array) wp_get_current_user()->roles : [];
    $organizerId = $userId > 0 && in_array($postType, ['organisateur', 'chasse', 'enigme'], true)
        ? (int) CoreServiceFactory::organizer($wpdb)->findIdForUser($userId)
        : 0;
    $organizerRole = defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur';
    $hasOrganizerRole = in_array($organizerRole, $roles, true);
    $hasExistingHunt = false;

    if ($postType === 'chasse' && $organizerId > 0 && !$hasOrganizerRole) {
        $hasExistingHunt = get_posts([
            'post_type' => 'chasse',
            'post_status' => 'any',
            'author' => $userId,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ]) !== [];
    }

    if ($userId > 0 && $postType === 'enigme' && !$huntId) {
        $huntId = filter_input(INPUT_GET, 'chasse_associee', FILTER_VALIDATE_INT);
    }

    $huntId = (int) $huntId;
    $hasValidHunt = $userId > 0 && $postType === 'enigme' && $huntId > 0
        && get_post_type($huntId) === 'chasse';
    $huntOrganizerId = $hasValidHunt
        ? (new RelationshipService())->normalizeId(get_field('chasse_cache_organisateur', $huntId))
        : null;

    return (new ContentCreationService())->canCreate(
        $authenticated,
        $administrator,
        $postType,
        $organizerId > 0,
        $hasOrganizerRole,
        $hasExistingHunt,
        $hasValidHunt,
        $huntOrganizerId !== null && $huntOrganizerId === $organizerId,
        $hasValidHunt ? (string) get_field('chasse_cache_statut_validation', $huntId) : ''
    );
}

/**
 * Determine whether the current user can modify application content.
 */
function utilisateur_peut_modifier_post($postId): bool
{
    $postId = (int) $postId;
    $validContext = is_user_logged_in() && $postId > 0;
    $administrator = $validContext && current_user_can('manage_options');
    $userId = $validContext && !$administrator ? get_current_user_id() : 0;
    $postType = $validContext && !$administrator ? (string) get_post_type($postId) : '';
    $associatedUser = false;
    $author = false;
    $ownerId = 0;
    $relationships = new RelationshipService();

    if ($postType === 'organisateur') {
        $associatedUsers = get_field('utilisateurs_associes', $postId);
        $associatedUsers = is_array($associatedUsers) ? $relationships->normalizeIds($associatedUsers) : [];
        $associatedUser = in_array($userId, $associatedUsers, true);
        $author = (int) get_post_field('post_author', $postId) === $userId;
    } elseif ($postType === 'chasse') {
        $ownerId = (int) $relationships->normalizeId(get_field('chasse_cache_organisateur', $postId));
    } elseif ($postType === 'enigme') {
        $huntId = (int) $relationships->normalizeId(get_field('enigme_chasse_associee', $postId));
        $ownerId = $huntId > 0
            ? (int) $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId))
            : 0;
    } elseif ($postType === 'indice') {
        $ownerId = (int) $relationships->normalizeId(get_field('indice_chasse_linked', $postId));
        if ($ownerId === 0) {
            $targets = get_field('indice_enigme_linked', $postId);
            $targetId = is_array($targets) ? $relationships->normalizeId(reset($targets)) : null;
            $ownerId = $targetId
                ? (int) $relationships->normalizeId(get_field('enigme_chasse_associee', $targetId))
                : 0;
        }
    }

    return (new ContentModificationService())->canModify(
        $validContext,
        $administrator,
        $postType,
        $associatedUser,
        $author,
        $ownerId > 0,
        $ownerId > 0 && utilisateur_peut_modifier_post($ownerId)
    );
}
