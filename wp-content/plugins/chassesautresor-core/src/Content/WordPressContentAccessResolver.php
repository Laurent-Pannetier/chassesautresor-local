<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Build content panel and field policies from WordPress state. */
final class WordPressContentAccessResolver
{
    public function canViewPanel(int $postId): bool
    {
        $authenticated = is_user_logged_in();
        $administrator = $authenticated && current_user_can('manage_options');
        $user = $authenticated ? wp_get_current_user() : null;
        $roles = $user ? (array) $user->roles : [];
        $organizer = !$administrator && (new OrganizerRoleService())->isOrganizer(
            $roles,
            defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur',
            defined('ROLE_ORGANISATEUR_CREATION') ? ROLE_ORGANISATEUR_CREATION : 'organisateur_creation'
        );
        $contentType = $authenticated ? (string) get_post_type($postId) : '';

        return (new ContentPanelAccessService())->canView(
            $authenticated,
            $administrator,
            $organizer,
            $administrator || ($organizer && utilisateur_peut_modifier_post($postId)),
            $contentType,
            $authenticated ? (string) get_post_status($postId) : '',
            $contentType === 'chasse' ? (string) get_field('chasse_cache_statut_validation', $postId) : '',
            $contentType === 'enigme' ? (string) get_field('enigme_cache_etat_systeme', $postId) : ''
        );
    }

    public function canEditFields(int $postId): bool
    {
        $canViewPanel = $this->canViewPanel($postId);
        $administrator = $canViewPanel && current_user_can('manage_options');
        $contentType = $canViewPanel && !$administrator ? (string) get_post_type($postId) : '';
        $huntId = $contentType === 'enigme'
            ? (int) (new RelationshipService())->normalizeId(get_field('enigme_chasse_associee', $postId))
            : 0;
        $statusSourceId = $contentType === 'enigme' ? $huntId : $postId;
        $hasHuntStatus = $contentType === 'chasse' || ($contentType === 'enigme' && $huntId > 0);
        $systemStatus = $contentType === 'enigme'
            ? (string) get_field('enigme_cache_etat_systeme', $postId)
            : ($contentType === 'indice' ? (string) get_field('indice_cache_etat_systeme', $postId) : '');

        return (new ContentFieldAccessService())->canEdit(
            $canViewPanel,
            $administrator,
            $contentType === 'organisateur' && !$administrator && utilisateur_peut_modifier_post($postId),
            $contentType,
            $canViewPanel ? (string) get_post_status($postId) : '',
            $hasHuntStatus ? (string) get_field('chasse_cache_statut_validation', $statusSourceId) : '',
            $hasHuntStatus ? (string) get_field('chasse_cache_statut', $statusSourceId) : '',
            $systemStatus,
            $huntId > 0,
            $huntId > 0 ? (string) get_post_status($huntId) : ''
        );
    }
}
