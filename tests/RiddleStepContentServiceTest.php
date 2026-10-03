<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepContentService;
use PHPUnit\Framework\TestCase;

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $value): string {
        return strip_tags($value);
    }
}

final class RiddleStepContentServiceTest extends TestCase {
    public function testCanValidateContentWithoutPersistingIt(): void {
        $service = new RiddleStepContentService();

        self::assertTrue($service->validate('Étape', '', 0, false));
        self::assertInstanceOf(WP_Error::class, $service->validate('', 'Contenu', 0));
        self::assertInstanceOf(WP_Error::class, $service->validate('Étape', '', 0));
    }

    public function testRequiresTextOrImage(): void {
        $result = (new RiddleStepContentService())->save(8, 'Étape', '', 0);

        self::assertInstanceOf(WP_Error::class, $result);
    }

    public function testPersistsValidContent(): void {
        $post = [];
        $fields = [];
        $result = (new RiddleStepContentService())->save(
            8,
            ' Porte ',
            '<p>Observez.</p>',
            12,
            true,
            static function (array $values) use (&$post): int {
                $post = $values;
                return 8;
            },
            static function (string $name, $value) use (&$fields): void {
                $fields[$name] = $value;
            }
        );

        self::assertTrue($result);
        self::assertSame('Porte', $post['post_title']);
        self::assertSame('publish', $post['post_status']);
        self::assertSame('<p>Observez.</p>', $fields['etape_contenu']);
        self::assertSame(12, $fields['etape_image']);
    }

    public function testAllowsEmptyContentForSelfContainedWidget(): void {
        $result = (new RiddleStepContentService())->save(
            8,
            'Code directionnel',
            '',
            0,
            false,
            static fn (array $values): int => 8,
            static function (string $name, $value): void {
            }
        );

        self::assertTrue($result);
    }
}
