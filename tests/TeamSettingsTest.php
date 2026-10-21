<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamSettings;
use PHPUnit\Framework\TestCase;

final class TeamSettingsTest extends TestCase
{
    public function testTheCapStaysBetweenOneAndTwenty(): void
    {
        $settings = new TeamSettings();

        self::assertNull($settings->accept(0));
        self::assertNull($settings->accept(21));
        self::assertSame(1, $settings->accept(1));
        self::assertSame(20, $settings->accept(20));
        self::assertSame(1, (new TeamSettings(0))->max());
        self::assertSame(20, (new TeamSettings(50))->max());
        self::assertSame(5, (new TeamSettings(5))->max());
    }
}
