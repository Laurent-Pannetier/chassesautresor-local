<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerCtaDecisionService;
use PHPUnit\Framework\TestCase;

final class OrganizerCtaDecisionServiceTest extends TestCase
{
    /** @dataProvider decisionProvider */
    public function testDecision(array $context, string $expected): void
    {
        self::assertSame($expected, (new OrganizerCtaDecisionService())->decide(...$context));
    }

    /** @return array<string, array{array{bool,bool,bool,bool,int,bool},string}> */
    public function decisionProvider(): array
    {
        return [
            'administrator' => [[true, false, false, false, 0, false], 'administrator'],
            'confirmation pending' => [[false, true, false, false, 0, false], 'resend_confirmation'],
            'hunt pending' => [[false, false, true, false, 8, true], 'resend_pending_hunt'],
            'existing profile' => [[false, false, false, true, 8, false], 'profile'],
            'new applicant' => [[false, false, false, false, 0, false], 'apply'],
            'fallback' => [[false, false, true, false, 0, false], 'create'],
        ];
    }
}
