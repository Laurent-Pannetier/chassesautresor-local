<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\AcfLocalJsonRegistrar;
use PHPUnit\Framework\TestCase;

final class AcfLocalJsonRegistrarTest extends TestCase
{
    public function testJsonLivesInThePluginAndIsLoadedWithExistingPaths(): void
    {
        $filters = [];
        AcfLocalJsonRegistrar::register(static function (...$arguments) use (&$filters): void {
            $filters[] = $arguments;
        });

        self::assertSame('acf/settings/save_json', $filters[0][0]);
        self::assertSame('acf/settings/load_json', $filters[1][0]);
        self::assertStringEndsWith(
            'wp-content/plugins/chassesautresor-core/acf-json',
            AcfLocalJsonRegistrar::directory()
        );
        self::assertSame(
            AcfLocalJsonRegistrar::directory(),
            AcfLocalJsonRegistrar::savePath('/tmp/acf')
        );
        self::assertSame(
            ['/other', AcfLocalJsonRegistrar::directory()],
            AcfLocalJsonRegistrar::loadPaths(['/other', AcfLocalJsonRegistrar::directory()])
        );
    }
}
