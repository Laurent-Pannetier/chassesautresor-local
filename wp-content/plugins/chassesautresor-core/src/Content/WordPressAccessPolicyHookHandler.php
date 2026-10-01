<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Own the global WordPress access policies required by the application.
 */
final class WordPressAccessPolicyHookHandler {
    private const RESTRICTED_POST_TYPES = ['organisateur', 'chasse', 'enigme'];
    private const SENSITIVE_CAPABILITIES = ['edit_post', 'delete_post', 'publish_post'];

    public static function register(callable $addAction, callable $addFilter): void {
        $addFilter('ajax_query_attachments_args', [self::class, 'restrictMediaToAuthor']);
        $addFilter('rest_attachment_query', [self::class, 'restrictMediaToAuthor']);
        $addFilter('ajax_query_attachments_args', [self::class, 'filterRiddleMedia'], 15);
        $addFilter('use_block_editor_for_post', [self::class, 'filterBlockEditor'], 10, 2);
        $addFilter('user_has_cap', [self::class, 'filterCapabilities'], 10, 4);
        $addAction('pre_get_posts', [self::class, 'extendVisiblePostStatuses']);
    }

    public static function restrictMediaToAuthor(array $query): array {
        $user = wp_get_current_user();
        if ($user->exists() && !in_array('administrator', (array) $user->roles, true)) {
            $query['author'] = $user->ID;
        }

        return $query;
    }

    public static function filterRiddleMedia(array $query): array {
        if (!isset($_REQUEST['post_id'])) {
            return $query;
        }

        $postId = (int) $_REQUEST['post_id'];
        $postType = get_post_type($postId);
        if ($postType === 'enigme') {
            $query['meta_query'] = [
                'relation' => 'AND',
                [
                    'key' => '_wp_attached_file',
                    'value' => '_enigmes/enigme-' . $postId . '/',
                    'compare' => 'LIKE',
                ],
            ];
        } elseif ($postType) {
            $query['meta_query'] = [
                'relation' => 'AND',
                [
                    'key' => '_wp_attached_file',
                    'value' => '_enigmes/',
                    'compare' => 'NOT LIKE',
                ],
            ];
        }

        return $query;
    }

    public static function filterBlockEditor(bool $useBlockEditor, $post): bool {
        return in_array('administrator', (array) wp_get_current_user()->roles, true)
            ? $useBlockEditor
            : false;
    }

    public static function filterCapabilities(array $allCaps, $capabilities, array $args, $user): array {
        if (in_array('administrator', (array) $user->roles, true)) {
            return $allCaps;
        }

        if (
            !is_array($capabilities)
            || $capabilities === []
            || !in_array($capabilities[0], self::SENSITIVE_CAPABILITIES, true)
            || !is_admin()
        ) {
            return $allCaps;
        }

        $postId = $args[2] ?? null;
        if (is_numeric($postId)) {
            $postType = get_post_type((int) $postId);
            $postAuthor = (int) get_post_field('post_author', (int) $postId);
            if (in_array($postType, self::RESTRICTED_POST_TYPES, true) && (int) $user->ID !== $postAuthor) {
                $allCaps[$capabilities[0]] = false;
            }
        } elseif (isset($_GET['post_type'])) {
            $postType = sanitize_text_field(wp_unslash($_GET['post_type']));
            if (in_array($postType, self::RESTRICTED_POST_TYPES, true)) {
                $allCaps[$capabilities[0]] = false;
            }
        }

        return $allCaps;
    }

    public static function extendVisiblePostStatuses($query): void {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        if (!$query->is_singular('enigme') && !$query->is_singular('chasse')) {
            return;
        }

        $isAuthenticated = is_user_logged_in();
        $isAdministrator = $isAuthenticated && current_user_can('manage_options');
        $roles = $isAuthenticated ? (array) wp_get_current_user()->roles : [];
        $isOrganizer = (new OrganizerRoleService())->isOrganizer(
            $roles,
            'organisateur',
            'organisateur_creation'
        );
        $statuses = (new ContentQueryAccessService())->getVisibleStatuses(
            $isAuthenticated,
            $isAdministrator,
            $isAuthenticated && !$isAdministrator && $isOrganizer
        );

        if ($statuses !== []) {
            $query->set('post_status', $statuses);
        }
    }
}
