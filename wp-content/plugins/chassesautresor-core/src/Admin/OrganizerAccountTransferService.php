<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

/**
 * Plan the move of one organizer profile from one account to another.
 *
 * Hunts, riddles and hints stay on the organizer post. The plan changes the
 * linked account, the authors, the hunt user meta and the organizer roles.
 */
final class OrganizerAccountTransferService
{
    private const SKIP_EXACT = [
        'utilisateurs_associes',
        'organisateur_id',
        'chasse_cache_organisateur',
        'enigme_chasse_associee',
        'enigme_organisateur_associe',
        'indice_chasse_linked',
        'indice_enigme_linked',
        'solution_chasse_linked',
        'solution_enigme_linked',
        'solution_cible_type',
        'etape_enigme_associee',
        'profil_public_email_contact',
        'trophee_chasse',
        'trophee_active',
        'iban',
        'bic',
    ];

    private const SKIP_PARTS = [
        'cout',
        'date',
        'statut',
        'status',
        'rank',
        'delai',
        'point',
        'duree',
        'ordre',
        'prix',
        'email',
        'validation',
        'minute',
        'seconde',
        'heure',
        'jour',
        'systeme',
        'complet',
        'token',
        'priority',
        'enabled',
        'channel',
        'active',
    ];

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    public function plan(array $snapshot): array
    {
        $plan = $this->emptyPlan($snapshot);
        $errors = $this->structuralErrors($snapshot);
        if ($errors !== []) {
            $plan['blocked'] = true;
            $plan['errors'] = $errors;

            return $plan;
        }

        $sourceId = (int) $snapshot['source_user_id'];
        $targetId = (int) $snapshot['target_user_id'];
        $organizerId = (int) $snapshot['organizer_id'];
        $targetOrganizers = $this->intList($snapshot['target_organizer_ids'] ?? []);
        $otherTargets = array_values(array_diff($targetOrganizers, [$organizerId]));
        if ($otherTargets !== []) {
            $plan['blocked'] = true;
            $plan['errors'] = ['target_already_linked'];

            return $plan;
        }

        return $this->fillPlan($plan, $snapshot, $sourceId, $targetId, $organizerId);
    }

    /**
     * Check the saved result while the administrator stays on their own account.
     *
     * @param array<string, mixed> $observed
     * @param array<string, mixed> $plan
     * @return array<int, array{code:string,ok:bool}>
     */
    public function audit(array $observed, array $plan): array
    {
        $targetId = (int) ($plan['target_user_id'] ?? 0);
        $sourceId = (int) ($plan['source_user_id'] ?? 0);
        $associated = $this->intList($observed['associated_user_ids'] ?? []);
        $targetRoles = $this->stringList($observed['target_roles'] ?? []);
        $sourceRoles = $this->stringList($observed['source_roles'] ?? []);
        $expected = $this->stringList($plan['roles_granted'] ?? []);
        $revoked = $this->stringList($plan['roles_revoked'] ?? []);
        $checks = [
            [
                'code' => 'target_associated',
                'ok' => $targetId > 0 && in_array($targetId, $associated, true),
            ],
            [
                'code' => 'author_is_target',
                'ok' => $targetId > 0 && (int) ($observed['organizer_author'] ?? 0) === $targetId,
            ],
            [
                'code' => 'source_detached',
                'ok' => $sourceId > 0 && !in_array($sourceId, $associated, true),
            ],
        ];
        if ($expected !== []) {
            $checks[] = [
                'code' => 'role_granted',
                'ok' => array_diff($expected, $targetRoles) === [],
            ];
        }
        if ($revoked !== []) {
            $checks[] = [
                'code' => 'role_revoked',
                'ok' => array_intersect($revoked, $sourceRoles) === [],
            ];
        }

        return $checks;
    }

    /**
     * @param mixed $value
     */
    public function containsId($value, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $value = $this->decodeSerialized($value);
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsId($item, $id)) {
                    return true;
                }
            }

            return false;
        }

        if (is_object($value) && isset($value->ID)) {
            return (int) $value->ID === $id;
        }

        return $this->normalizeId($value) === $id;
    }

    /**
     * Attachment ids stored on the organizer tree, ignoring business numbers.
     *
     * @param array<int, array{key?:string,value?:mixed}> $rows
     * @return int[]
     */
    public function candidateAttachmentIds(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $key = (string) ($row['key'] ?? '');
            if ($this->skipsMetaKey($key)) {
                continue;
            }
            foreach ($this->integerLeaves($row['value'] ?? null) as $id) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return string[]
     */
    private function structuralErrors(array $snapshot): array
    {
        $errors = [];
        $status = (string) ($snapshot['organizer_status'] ?? '');
        $type = (string) ($snapshot['organizer_type'] ?? '');
        $organizerId = (int) ($snapshot['organizer_id'] ?? 0);
        if ($organizerId <= 0 || $type !== 'organisateur' || in_array($status, ['', 'trash', 'auto-draft'], true)) {
            $errors[] = 'invalid_organizer';
        }
        if (empty($snapshot['source_exists'])) {
            $errors[] = 'missing_source';
        }
        if (empty($snapshot['target_exists'])) {
            $errors[] = 'missing_target';
        }
        $sourceId = (int) ($snapshot['source_user_id'] ?? 0);
        $targetId = (int) ($snapshot['target_user_id'] ?? 0);
        if ($sourceId > 0 && $sourceId === $targetId && !empty($snapshot['source_exists'])) {
            $errors[] = 'same_user';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function fillPlan(
        array $plan,
        array $snapshot,
        int $sourceId,
        int $targetId,
        int $organizerId
    ): array {
        $associated = $this->intList($snapshot['associated_user_ids'] ?? []);
        $posts = is_array($snapshot['posts'] ?? null) ? $snapshot['posts'] : [];
        $attachments = is_array($snapshot['attachments'] ?? null) ? $snapshot['attachments'] : [];
        $contentIds = $this->contentIds($organizerId, $posts);
        $includeOrphans = !empty($snapshot['include_orphan_attachments']);
        $sourceHasAnotherOrganizer = $this->intList($snapshot['source_other_organizer_ids'] ?? []) !== [];
        if (!$this->hasStake($snapshot, $associated, $posts, $attachments, $contentIds, $sourceId)) {
            $plan['blocked'] = true;
            $plan['errors'] = $this->targetOwnsOrganizer($snapshot, $associated, $targetId, $organizerId)
                ? ['already_transferred']
                : ['source_not_linked'];

            return $plan;
        }

        $selected = $this->selectedAttachments(
            $attachments,
            $sourceId,
            $contentIds,
            $includeOrphans && !$sourceHasAnotherOrganizer
        );

        $plan['associated_user_ids'] = $this->replaceUser($associated, $sourceId, $targetId);
        $plan['force_author_ids'] = (int) ($snapshot['organizer_author'] ?? 0) === $targetId ? [] : [$organizerId];
        $plan['reauthor_ids'] = $this->reauthorIds($posts, $selected, $sourceId);
        $plan['hunt_user_ids'] = $this->huntUserIds($posts, $sourceId, $targetId);
        $plan['roles_granted'] = array_values(array_intersect(
            $this->stringList($snapshot['source_roles'] ?? []),
            $this->organizerRoles()
        ));
        $plan['roles_revoked'] = $sourceHasAnotherOrganizer ? [] : $plan['roles_granted'];
        $plan['posts'] = $this->changedPosts($snapshot, $posts, $plan['force_author_ids'], $plan['reauthor_ids']);
        $plan['attachments'] = $selected['rows'];
        $plan['foreign_author_count'] = $this->foreignAuthorCount($posts, $sourceId, $targetId);
        $plan['shared_attachment_count'] = $selected['shared'];
        $plan['orphan_skipped_count'] = $selected['skipped_orphans'];
        $plan['notices'] = $this->notices($plan, $snapshot, $sourceHasAnotherOrganizer);

        if (!$this->hasWork($plan, $associated)) {
            $plan['blocked'] = true;
            $targetOrganizers = $this->intList($snapshot['target_organizer_ids'] ?? []);
            $plan['errors'] = in_array($organizerId, $targetOrganizers, true) || in_array($targetId, $associated, true)
                ? ['already_transferred']
                : ['source_not_linked'];
        }

        return $plan;
    }

    /**
     * The source account must already own this organizer, its content, or a file on it.
     *
     * @param array<string, mixed> $snapshot
     * @param int[] $associated
     * @param array<int, mixed> $posts
     * @param array<int, mixed> $attachments
     * @param int[] $contentIds
     */
    private function hasStake(
        array $snapshot,
        array $associated,
        array $posts,
        array $attachments,
        array $contentIds,
        int $sourceId
    ): bool {
        if (in_array($sourceId, $associated, true) || (int) ($snapshot['organizer_author'] ?? 0) === $sourceId) {
            return true;
        }
        foreach ($posts as $post) {
            if (!is_array($post)) {
                continue;
            }
            if ((int) ($post['author'] ?? 0) === $sourceId) {
                return true;
            }
            if ((string) ($post['type'] ?? '') === 'chasse' && (int) ($post['organizer_user_id'] ?? 0) === $sourceId) {
                return true;
            }
        }
        foreach ($attachments as $attachment) {
            if (!is_array($attachment) || (int) ($attachment['author'] ?? 0) !== $sourceId) {
                continue;
            }
            $parentId = (int) ($attachment['parent_id'] ?? 0);
            if (!empty($attachment['referenced']) || ($parentId > 0 && in_array($parentId, $contentIds, true))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param int[] $associated
     */
    private function targetOwnsOrganizer(array $snapshot, array $associated, int $targetId, int $organizerId): bool
    {
        return in_array($targetId, $associated, true)
            || in_array($organizerId, $this->intList($snapshot['target_organizer_ids'] ?? []), true);
    }

    /**
     * @param array<int, mixed> $posts
     * @return int[]
     */
    private function contentIds(int $organizerId, array $posts): array
    {
        $ids = [$organizerId => $organizerId];
        foreach ($posts as $post) {
            if (!is_array($post)) {
                continue;
            }
            $id = (int) ($post['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param array<int, mixed> $attachments
     * @param int[] $contentIds
     * @return array{rows:array<int,array<string,mixed>>,shared:int,skipped_orphans:int}
     */
    private function selectedAttachments(
        array $attachments,
        int $sourceId,
        array $contentIds,
        bool $includeOrphans
    ): array {
        $rows = [];
        $shared = 0;
        $skippedOrphans = 0;
        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }
            $id = (int) ($attachment['id'] ?? 0);
            $author = (int) ($attachment['author'] ?? 0);
            $parentId = (int) ($attachment['parent_id'] ?? 0);
            $referenced = !empty($attachment['referenced']);
            $inTree = $parentId > 0 && in_array($parentId, $contentIds, true);
            if ($id <= 0) {
                continue;
            }
            if ($author !== $sourceId) {
                if ($referenced || $inTree) {
                    $shared++;
                }
                continue;
            }
            $reason = $this->attachmentReason($referenced, $inTree, $parentId);
            if ($reason === '') {
                continue;
            }
            if ($reason === 'orphan' && !$includeOrphans) {
                $skippedOrphans++;
                continue;
            }
            $rows[] = [
                'id' => $id,
                'title' => (string) ($attachment['title'] ?? ''),
                'reason' => $reason,
            ];
        }

        return ['rows' => $rows, 'shared' => $shared, 'skipped_orphans' => $skippedOrphans];
    }

    private function attachmentReason(bool $referenced, bool $inTree, int $parentId): string
    {
        if ($referenced) {
            return 'referenced';
        }
        if ($inTree) {
            return 'parent';
        }
        if ($parentId === 0) {
            return 'orphan';
        }

        return '';
    }

    /**
     * @param array<int, mixed> $posts
     * @param array{rows:array<int,array<string,mixed>>} $selected
     * @return int[]
     */
    private function reauthorIds(array $posts, array $selected, int $sourceId): array
    {
        $ids = [];
        foreach ($posts as $post) {
            if (!is_array($post)) {
                continue;
            }
            $id = (int) ($post['id'] ?? 0);
            if ($id > 0 && (int) ($post['author'] ?? 0) === $sourceId) {
                $ids[] = $id;
            }
        }
        foreach ($selected['rows'] as $row) {
            $ids[] = (int) $row['id'];
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, mixed> $posts
     * @return array<int, int>
     */
    private function huntUserIds(array $posts, int $sourceId, int $targetId): array
    {
        $updates = [];
        foreach ($posts as $post) {
            if (!is_array($post) || (string) ($post['type'] ?? '') !== 'chasse') {
                continue;
            }
            $id = (int) ($post['id'] ?? 0);
            $owner = (int) ($post['organizer_user_id'] ?? 0);
            if ($id > 0 && ($owner === $sourceId || $owner === 0)) {
                $updates[$id] = $targetId;
            }
        }

        return $updates;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<int, mixed> $posts
     * @param int[] $forced
     * @param int[] $reauthored
     * @return array<int, array<string, mixed>>
     */
    private function changedPosts(array $snapshot, array $posts, array $forced, array $reauthored): array
    {
        $rows = [];
        if ($forced !== []) {
            $rows[] = [
                'id' => (int) $snapshot['organizer_id'],
                'type' => 'organisateur',
                'title' => (string) ($snapshot['organizer_title'] ?? ''),
                'status' => (string) ($snapshot['organizer_status'] ?? ''),
            ];
        }
        foreach ($posts as $post) {
            if (!is_array($post) || !in_array((int) ($post['id'] ?? 0), $reauthored, true)) {
                continue;
            }
            $rows[] = [
                'id' => (int) $post['id'],
                'type' => (string) ($post['type'] ?? ''),
                'title' => (string) ($post['title'] ?? ''),
                'status' => (string) ($post['status'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, mixed> $posts
     */
    private function foreignAuthorCount(array $posts, int $sourceId, int $targetId): int
    {
        $count = 0;
        foreach ($posts as $post) {
            if (!is_array($post)) {
                continue;
            }
            $author = (int) ($post['author'] ?? 0);
            if ($author > 0 && $author !== $sourceId && $author !== $targetId) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param int[] $associated
     * @return int[]
     */
    private function replaceUser(array $associated, int $sourceId, int $targetId): array
    {
        $next = [];
        $replaced = false;
        foreach ($associated as $id) {
            if ($id === $sourceId) {
                $next[] = $targetId;
                $replaced = true;
                continue;
            }
            $next[] = $id;
        }
        if (!$replaced) {
            $next[] = $targetId;
        }

        return array_values(array_unique($next));
    }

    /**
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $snapshot
     * @return string[]
     */
    private function notices(array $plan, array $snapshot, bool $sourceHasAnotherOrganizer): array
    {
        $notices = [];
        $contact = strtolower(trim((string) ($snapshot['contact_email'] ?? '')));
        $targetEmail = strtolower(trim((string) ($snapshot['target_email'] ?? '')));
        if ($contact !== '' && $contact !== $targetEmail) {
            $notices[] = 'contact_email';
        }
        if ((int) $plan['foreign_author_count'] > 0) {
            $notices[] = 'foreign_authors';
        }
        if ((int) $plan['shared_attachment_count'] > 0) {
            $notices[] = 'shared_attachments';
        }
        if ((int) $plan['orphan_skipped_count'] > 0) {
            $notices[] = 'orphans_skipped';
        }
        if ($sourceHasAnotherOrganizer && $plan['roles_granted'] !== []) {
            $notices[] = 'source_keeps_roles';
        }
        if ($plan['roles_granted'] === []) {
            $notices[] = 'no_organizer_role';
        }

        return $notices;
    }

    /**
     * @param array<string, mixed> $plan
     * @param int[] $associated
     */
    private function hasWork(array $plan, array $associated): bool
    {
        return $plan['associated_user_ids'] !== $associated
            || $plan['force_author_ids'] !== []
            || $plan['reauthor_ids'] !== []
            || $plan['hunt_user_ids'] !== []
            || $plan['roles_revoked'] !== [];
    }

    /**
     * @return string[]
     */
    private function organizerRoles(): array
    {
        return [
            defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur',
            defined('ROLE_ORGANISATEUR_CREATION') ? ROLE_ORGANISATEUR_CREATION : 'organisateur_creation',
        ];
    }

    /**
     * @param mixed $value
     * @return int[]
     */
    private function integerLeaves($value): array
    {
        $value = $this->decodeSerialized($value);
        if (is_array($value)) {
            $ids = [];
            foreach ($value as $item) {
                foreach ($this->integerLeaves($item) as $id) {
                    $ids[] = $id;
                }
            }

            return $ids;
        }

        $id = $this->normalizeId($value);

        return $id === null ? [] : [$id];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function decodeSerialized($value)
    {
        if (!is_string($value) || preg_match('/^[abdiOs]:/', $value) !== 1) {
            return $value;
        }

        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if ($decoded === false && $value !== 'b:0;') {
            return $value;
        }

        return $decoded;
    }

    /**
     * @param mixed $value
     * @return int[]
     */
    private function intList($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $item) {
            $id = $this->normalizeId($item);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private function stringList($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $items = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * @param mixed $value
     */
    private function normalizeId($value): ?int
    {
        if (is_array($value) && array_key_exists('ID', $value)) {
            return $this->normalizeId($value['ID']);
        }
        if (is_object($value) && isset($value->ID)) {
            return $this->normalizeId($value->ID);
        }
        if (is_int($value) || (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1)) {
            return (int) $value;
        }

        return null;
    }

    private function skipsMetaKey(string $key): bool
    {
        if ($key === '' || strncmp($key, '_', 1) === 0 || in_array($key, self::SKIP_EXACT, true)) {
            return true;
        }
        $lower = strtolower($key);
        foreach (self::SKIP_PARTS as $part) {
            if (str_contains($lower, $part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function emptyPlan(array $snapshot): array
    {
        return [
            'blocked' => false,
            'errors' => [],
            'notices' => [],
            'organizer_id' => (int) ($snapshot['organizer_id'] ?? 0),
            'organizer_title' => (string) ($snapshot['organizer_title'] ?? ''),
            'source_user_id' => (int) ($snapshot['source_user_id'] ?? 0),
            'target_user_id' => (int) ($snapshot['target_user_id'] ?? 0),
            'associated_user_ids' => [],
            'force_author_ids' => [],
            'reauthor_ids' => [],
            'hunt_user_ids' => [],
            'roles_granted' => [],
            'roles_revoked' => [],
            'posts' => [],
            'attachments' => [],
            'foreign_author_count' => 0,
            'shared_attachment_count' => 0,
            'orphan_skipped_count' => 0,
            'contact_email' => (string) ($snapshot['contact_email'] ?? ''),
            'target_email' => (string) ($snapshot['target_email'] ?? ''),
        ];
    }
}
