<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Create organizers and maintain their required user relationship.
 */
class OrganizerCreationService {
    public const DEFAULT_LOGO_ID = 3927;

    /**
     * @param callable(array<string, mixed>): mixed $insertPost
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(mixed): bool $isError
     * @return array{organizer_id: int|null, created: bool, error: string|null}
     */
    public function create(
        int $userId,
        int $existingOrganizerId,
        string $defaultTitle,
        string $email,
        callable $insertPost,
        callable $updateField,
        callable $isError
    ): array {
        if ($userId <= 0) {
            return ['organizer_id' => null, 'created' => false, 'error' => 'invalid_user'];
        }

        if ($existingOrganizerId > 0) {
            return ['organizer_id' => $existingOrganizerId, 'created' => false, 'error' => null];
        }

        $organizerId = $insertPost([
            'post_type' => 'organisateur',
            'post_status' => 'pending',
            'post_title' => $defaultTitle,
            'post_author' => $userId,
        ]);

        if ($isError($organizerId) || (int) $organizerId <= 0) {
            return ['organizer_id' => null, 'created' => false, 'error' => 'creation_failed'];
        }

        $organizerId = (int) $organizerId;
        $updateField('utilisateurs_associes', [(string) $userId], $organizerId);
        $updateField('logo_organisateur', self::DEFAULT_LOGO_ID, $organizerId);
        $updateField('profil_public_email_contact', $email, $organizerId);

        return ['organizer_id' => $organizerId, 'created' => true, 'error' => null];
    }

    /**
     * @param mixed $associatedUsers
     * @param callable(string, mixed, int): mixed $updateField
     */
    public function ensureAuthorRelationship(
        int $organizerId,
        string $postType,
        bool $isAutosave,
        int $authorId,
        $associatedUsers,
        callable $updateField
    ): bool {
        if (
            $organizerId <= 0
            || $postType !== 'organisateur'
            || $isAutosave
            || $authorId <= 0
            || (is_array($associatedUsers) && $associatedUsers !== [])
        ) {
            return false;
        }

        $updateField('utilisateurs_associes', [(string) $authorId], $organizerId);

        return true;
    }
}
