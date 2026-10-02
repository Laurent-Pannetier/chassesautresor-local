<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepCreationService;
use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(public string $code = '', public string $message = '') {
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($value): bool {
        return $value instanceof WP_Error;
    }
}

final class RiddleStepCreationServiceTest extends TestCase {
    public function testCreatesADraftStepWithSafeDefaults(): void {
        $inserted = [];
        $updated = [];
        $service = new RiddleStepCreationService();

        $stepId = $service->create(
            42,
            7,
            ' Le cadenas ',
            3,
            static fn (int $postId): string => $postId === 42 ? 'enigme' : '',
            static function (array $post) use (&$inserted): int {
                $inserted = $post;
                return 80;
            },
            static function (string $field, $value, int $postId) use (&$updated): void {
                $updated[$field] = [$value, $postId];
            }
        );

        self::assertSame(80, $stepId);
        self::assertSame(RiddleStepPostTypeRegistrar::POST_TYPE, $inserted['post_type']);
        self::assertSame('draft', $inserted['post_status']);
        self::assertSame('Le cadenas', $inserted['post_title']);
        self::assertSame(3, $inserted['menu_order']);
        self::assertSame([42, 80], $updated['etape_enigme_associee']);
    }

    public function testRejectsAnInvalidRiddleBeforeInsertion(): void {
        $insertCalled = false;
        $result = (new RiddleStepCreationService())->create(
            42,
            7,
            '',
            0,
            static fn (): string => 'post',
            static function () use (&$insertCalled): int {
                $insertCalled = true;
                return 80;
            },
            static function (): void {
            }
        );

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertFalse($insertCalled);
    }
}
