<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Own the supported avatar formats and custom avatar substitution policy.
 */
final class UserAvatarHookHandler
{
    public static function register(callable $addFilter): void
    {
        $addFilter('upload_mimes', [self::class, 'allowImageMimes']);
        $addFilter('get_avatar', [self::class, 'replaceAvatar'], 10, 5);
    }

    public static function allowImageMimes(array $mimes): array
    {
        $mimes['jpg'] = 'image/jpeg';
        $mimes['jpeg'] = 'image/jpeg';
        $mimes['png'] = 'image/png';
        $mimes['gif'] = 'image/gif';
        $mimes['webp'] = 'image/webp';

        return $mimes;
    }

    public static function replaceAvatar(string $avatar, $idOrEmail, int $size, string $default, string $alt): string
    {
        $userId = self::resolveUserId($idOrEmail);
        if ($userId <= 0) {
            return $avatar;
        }

        $avatarUrl = (string) get_user_meta($userId, 'user_avatar', true);
        if ($avatarUrl === '') {
            return $avatar;
        }

        return sprintf(
            "<img src='%s' alt='%s' width='%d' height='%d' class='avatar avatar-%d photo' />",
            esc_url($avatarUrl),
            esc_attr($alt),
            $size,
            $size,
            $size
        );
    }

    private static function resolveUserId($idOrEmail): int
    {
        if (is_numeric($idOrEmail)) {
            return (int) $idOrEmail;
        }

        if (is_object($idOrEmail) && isset($idOrEmail->user_id)) {
            return (int) $idOrEmail->user_id;
        }

        if (is_string($idOrEmail)) {
            $user = get_user_by('email', $idOrEmail);
            return $user ? (int) $user->ID : 0;
        }

        return 0;
    }
}
