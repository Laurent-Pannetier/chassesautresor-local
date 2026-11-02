<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleStepImageSaveHookHandler;
use PHPUnit\Framework\TestCase;

final class RiddleStepImageSaveHookHandlerTest extends TestCase
{
    public function testSecuresImagesWhenAStepIsSaved(): void
    {
        $hooks = [];
        RiddleStepImageSaveHookHandler::register(static function (...$arguments) use (&$hooks): void {
            $hooks[] = $arguments;
        });

        self::assertSame('acf/save_post', $hooks[0][0]);
        self::assertSame([RiddleStepImageSaveHookHandler::class, 'secureSavedStep'], $hooks[0][1]);
        self::assertSame(20, $hooks[0][2]);
        self::assertSame('save_post_enigme_etape', $hooks[1][0]);
    }

    public function testThemeComicPagesDoNotPersistStepImages(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/visuels.php'
        );

        self::assertStringNotContainsString('ensureProtected', $source);
        self::assertStringNotContainsString('update_field', $source);
        self::assertStringContainsString('function enigme_collect_step_comic_pages', $source);
    }
}
