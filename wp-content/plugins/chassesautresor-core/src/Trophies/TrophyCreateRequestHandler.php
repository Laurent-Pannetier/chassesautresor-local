<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

use ChassesAuTresor\Core\Teams\TeamImageUploader;

/** Accept the administrator trophy form posted from the wall. */
final class TrophyCreateRequestHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('admin_post_cat_trophy_create', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        $post = self::post();
        $allowed = function_exists('cat_trophy_viewer_is_admin')
            && cat_trophy_viewer_is_admin((int) get_current_user_id());
        $nonceValid = self::nonceValid($post);
        $notice = self::process(
            $post,
            $allowed && $nonceValid ? self::image() : ['id' => null, 'error' => false],
            $allowed,
            $nonceValid,
            static fn (int $huntId): bool => function_exists('get_post_type')
                && get_post_type($huntId) === 'chasse',
            'wp_insert_post',
            static function (string $key, mixed $value, int $postId): void {
                if (function_exists('update_field')) {
                    update_field($key, $value, $postId);

                    return;
                }
                if (function_exists('update_post_meta')) {
                    update_post_meta($postId, $key, $value);
                }
            },
            (int) get_current_user_id()
        );
        if ($notice === 'forbidden') {
            wp_die(esc_html__('Accès refusé.', 'chassesautresor-com'));
        }

        $target = function_exists('cat_trophy_create_return_url')
            ? cat_trophy_create_return_url($notice === 'ok' ? '' : $notice)
            : home_url('/trophees/');
        wp_safe_redirect($target);
        exit;
    }

    /**
     * @param array<string, mixed> $post
     * @param array{id: ?int, error: bool} $image
     * @param callable(int): bool $huntIsValid
     * @param callable(array<string, mixed>): mixed $insertPost
     * @param callable(string, mixed, int): void $updateField
     */
    public static function process(
        array $post,
        array $image,
        bool $allowed,
        bool $nonceValid,
        callable $huntIsValid,
        callable $insertPost,
        callable $updateField,
        int $authorId
    ): string {
        if (!$allowed || !$nonceValid) {
            return 'forbidden';
        }
        if (!empty($image['error'])) {
            return 'image';
        }

        $draft = TrophyDraft::from([
            'title' => $post['trophee_titre'] ?? '',
            'description' => $post['trophee_texte'] ?? '',
            'hunt_id' => $post['trophee_chasse'] ?? 0,
            'scope' => $post['trophee_attribution'] ?? 'user',
            'event_key' => $post['trophee_cle'] ?? '',
        ], (int) ($image['id'] ?? 0));
        if ($draft === null || ($draft->huntId > 0 && !$huntIsValid($draft->huntId))) {
            return 'invalid';
        }

        $trophyId = (new TrophyCreationService())->create($draft, $authorId, $insertPost, $updateField);

        return $trophyId > 0 ? 'ok' : 'save';
    }

    /** @return array<string, mixed> */
    private static function post(): array
    {
        $post = $_POST;
        if (function_exists('wp_unslash')) {
            $post = wp_unslash($post);
        }

        return is_array($post) ? $post : [];
    }

    /** @param array<string, mixed> $post */
    private static function nonceValid(array $post): bool
    {
        $nonce = (string) ($post['cat_trophy_nonce'] ?? '');

        return $nonce !== ''
            && function_exists('wp_verify_nonce')
            && wp_verify_nonce($nonce, 'cat_trophy_create') !== false;
    }

    /** @return array{id: ?int, error: bool} */
    private static function image(): array
    {
        $file = $_FILES['trophee_image'] ?? null;
        if (!is_array($file)) {
            return ['id' => null, 'error' => false];
        }

        return (new TeamImageUploader())->store($file);
    }
}
