<?php

use ChassesAuTresor\Core\Content\HuntFilterRequestService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFilterRequestService.php';

final class HuntFilterRequestServiceTest extends TestCase
{
    public function testItNormalizesOnlySupportedFilters(): void
    {
        $filters = HuntFilterRequestService::normalize([
            'status' => 'termine',
            'cost' => ['points', 'invalid', 'gratuit'],
            'search' => ' treasure ',
        ]);

        self::assertSame('termine', $filters['statut']);
        self::assertSame(['gratuit', 'points'], $filters['cout']);
        self::assertSame(' treasure ', $filters['search']);
    }

    public function testItRejectsUnsupportedAndNonScalarValues(): void
    {
        self::assertSame(['cout' => []], HuntFilterRequestService::normalize([
            'status' => 'private',
            'cost' => ['invalid'],
            'search' => ['invalid'],
        ]));
    }
}
