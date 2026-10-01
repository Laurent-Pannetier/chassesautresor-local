<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media {
    function get_user_meta(int $userId, string $key, bool $single)
    {
        return $GLOBALS['avatar_test_meta'][$userId][$key] ?? '';
    }

    function get_user_by(string $field, string $value)
    {
        return $value === 'player@example.test' ? (object) ['ID' => 42] : false;
    }

    function esc_url(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES);
    }

    function esc_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}

namespace {
    use ChassesAuTresor\Core\Media\UserAvatarHookHandler;
    use PHPUnit\Framework\TestCase;

    require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/UserAvatarHookHandler.php';

    final class UserAvatarHookHandlerTest extends TestCase
    {
        protected function setUp(): void
        {
            $GLOBALS['avatar_test_meta'] = [];
        }

        public function testRegistersAvatarPolicyFilters(): void
        {
            $filters = [];
            UserAvatarHookHandler::register(
                static function (...$arguments) use (&$filters): void {
                    $filters[] = $arguments;
                }
            );

            self::assertSame('upload_mimes', $filters[0][0]);
            self::assertSame('get_avatar', $filters[1][0]);
            self::assertSame(5, $filters[1][3]);
        }

        public function testAllowsSupportedAvatarImageFormats(): void
        {
            $mimes = UserAvatarHookHandler::allowImageMimes(['pdf' => 'application/pdf']);

            self::assertSame('image/jpeg', $mimes['jpg']);
            self::assertSame('image/png', $mimes['png']);
            self::assertSame('image/gif', $mimes['gif']);
            self::assertSame('image/webp', $mimes['webp']);
            self::assertSame('application/pdf', $mimes['pdf']);
        }

        public function testReplacesAvatarFromAnEmailAddress(): void
        {
            $GLOBALS['avatar_test_meta'][42]['user_avatar'] = 'https://example.test/avatar.webp';

            $avatar = UserAvatarHookHandler::replaceAvatar(
                '<img src="default">',
                'player@example.test',
                96,
                '',
                'Player'
            );

            self::assertStringContainsString('https://example.test/avatar.webp', $avatar);
            self::assertStringContainsString("class='avatar avatar-96 photo'", $avatar);
        }
    }
}
