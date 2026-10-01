<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatusBadgeService;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

final class HuntStatusBadgeServiceTest extends TestCase
{
    public function testCorrectionBadgeIsPortableWithoutThemeIconRenderer(): void
    {
        $badge = (new HuntStatusBadgeService())->build('revision', 'correction');

        self::assertSame('revision', $badge['statut']);
        self::assertSame('correction', $badge['label']);
        self::assertSame('edition', $badge['icon_name']);
        self::assertSame('', $badge['icon_html']);
    }

    public function testPaidHuntUsesInProgressPresentation(): void
    {
        $badge = (new HuntStatusBadgeService())->build('payante', null);

        self::assertSame('en_cours', $badge['statut']);
        self::assertSame('en cours', $badge['label']);
    }
}
