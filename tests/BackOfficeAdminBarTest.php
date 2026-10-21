<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\BackOfficeAccessHookHandler;
use PHPUnit\Framework\TestCase;

class BackOfficeAdminBarTest extends TestCase
{
    public function testOrganizerRolesLoseTheFrontEndToolbar(): void
    {
        self::assertFalse(BackOfficeAccessHookHandler::shouldShowAdminBar(true, ['organisateur']));
        self::assertFalse(BackOfficeAccessHookHandler::shouldShowAdminBar(true, ['organisateur_creation']));
        self::assertFalse(
            BackOfficeAccessHookHandler::shouldShowAdminBar(true, ['customer', 'organisateur'])
        );
    }

    public function testAdministratorsKeepTheToolbarEvenWithAnOrganizerRole(): void
    {
        self::assertTrue(
            BackOfficeAccessHookHandler::shouldShowAdminBar(true, ['administrator', 'organisateur'])
        );
    }

    public function testOtherRolesKeepTheIncomingDecision(): void
    {
        self::assertTrue(BackOfficeAccessHookHandler::shouldShowAdminBar(true, ['customer']));
        self::assertFalse(BackOfficeAccessHookHandler::shouldShowAdminBar(false, ['organisateur']));
        self::assertFalse(BackOfficeAccessHookHandler::shouldShowAdminBar(false, ['administrator']));
    }
}
