<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountLegacyRouteHandler;
use PHPUnit\Framework\TestCase;

final class AccountLegacyRouteHandlerTest extends TestCase
{
    public function testPublicOutilsPageIsNotTreatedAsALegacyAccountUrl(): void
    {
        $this->assertNull(AccountLegacyRouteHandler::legacyTarget('outils', '', false));
        $this->assertNull(AccountLegacyRouteHandler::legacyTarget('outils', '', true));
    }

    public function testLegacyOutilsUrlsOpenTheShellTools(): void
    {
        $this->assertSame(
            '/outils/',
            AccountLegacyRouteHandler::legacyTarget('mon-compte/outils', '', false)
        );
        $this->assertSame(
            '/outils/',
            AccountLegacyRouteHandler::legacyTarget('mon-compte', 'outils', true)
        );
    }

    public function testOtherLegacySectionsStillOpenTheAccountDashboard(): void
    {
        $this->assertSame(
            '/mon-compte/',
            AccountLegacyRouteHandler::legacyTarget('mon-compte/statistiques', '', false)
        );
        $this->assertSame(
            '/mon-compte/?section=statistiques',
            AccountLegacyRouteHandler::legacyTarget('mon-compte/statistiques', '', true)
        );
    }
}
