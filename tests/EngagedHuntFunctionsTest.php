<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/engaged-hunt-functions.php';

final class EngagedHuntFunctionsTest extends TestCase
{
    public function testPaginationClampsPageAndSlicesIdentifiers(): void
    {
        self::assertSame([
            'ids' => [4, 5],
            'page' => 2,
            'total_pages' => 2,
            'total_items' => 5,
        ], ca_prepare_engaged_hunts_pagination([1, 2, 3, 4, 5], 8, 3));
    }

    public function testPageParameterRemainsBackwardCompatible(): void
    {
        self::assertSame('engaged-page', ca_get_engaged_hunts_page_param());
    }
}
