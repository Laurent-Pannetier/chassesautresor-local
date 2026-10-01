<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Protect native WordPress creation and editing screens for application content.
 */
final class ContentScreenAccessHookHandler {
    public static function register(callable $addAction): void {
        $addAction('load-post-new.php', [self::class, 'handleCreateScreen']);
        $addAction('load-post.php', [self::class, 'handleEditScreen']);
    }

    public static function handleCreateScreen(): void {
        if (!is_admin() || !isset($_GET['post_type'])) {
            return;
        }

        $postType = sanitize_text_field(wp_unslash($_GET['post_type']));
        if ($postType === '' || current_user_can('manage_options')) {
            return;
        }

        $canCreate = function_exists('utilisateur_peut_creer_post')
            ? utilisateur_peut_creer_post($postType)
            : current_user_can('edit_posts');
        self::redirectWhenDenied((bool) $canCreate);
    }

    public static function handleEditScreen(): void {
        if (!is_admin() || !isset($_GET['post'])) {
            return;
        }

        $postId = (int) $_GET['post'];
        if ($postId <= 0 || current_user_can('manage_options')) {
            return;
        }

        $canModify = function_exists('utilisateur_peut_modifier_post')
            ? utilisateur_peut_modifier_post($postId)
            : current_user_can('edit_post', $postId);
        self::redirectWhenDenied((bool) $canModify);
    }

    private static function redirectWhenDenied(bool $isAllowed): void {
        if (is_user_logged_in() && $isAllowed) {
            return;
        }

        wp_safe_redirect(home_url('/mon-compte/'));
        exit;
    }
}
