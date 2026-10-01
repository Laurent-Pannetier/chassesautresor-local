<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntDateValidationHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntDateValidationHookHandler.php';

final class HuntDateValidationHookHandlerTest extends TestCase
{
    public function testRegistersAcfEndDateValidation(): void
    {
        $filters = [];
        HuntDateValidationHookHandler::register(
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame('acf/validate_value/name=date_de_fin', $filters[0][0]);
        self::assertSame(4, $filters[0][3]);
    }

    public function testRejectsEndDateBeforeStartDate(): void
    {
        self::assertSame(
            'before_start',
            HuntDateValidationHookHandler::validateDates('2026-10-15', '2026-10-14', '', '2026-10-01')
        );
    }

    public function testRejectsPastEndDateWhenHuntStartsNow(): void
    {
        self::assertSame(
            'before_today',
            HuntDateValidationHookHandler::validateDates('', '2026-09-30', 'maintenant', '2026-10-01')
        );
    }
}
