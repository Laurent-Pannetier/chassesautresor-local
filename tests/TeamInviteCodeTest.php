<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamInviteCode;
use PHPUnit\Framework\TestCase;

final class TeamInviteCodeTest extends TestCase
{
    public function testCodesAvoidAmbiguousCharacters(): void
    {
        $codes = new TeamInviteCode();
        for ($index = 0; $index < 40; $index++) {
            $code = $codes->generate();
            self::assertSame(8, strlen($code));
            self::assertSame(1, preg_match('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/', $code));
            self::assertSame(0, preg_match('/[01OI]/', $code));
        }
    }

    public function testNormalizeDropsSpacesAndDashes(): void
    {
        $codes = new TeamInviteCode();

        self::assertSame('ABCD2345', $codes->normalize(' ab-cd 2345 '));
        self::assertTrue($codes->isValid($codes->normalize('ab-cd 2345')));
        self::assertFalse($codes->isValid('ABCD234O'));
        self::assertFalse($codes->isValid('SHORT'));
    }
}
