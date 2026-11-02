<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddlePaymentPolicy;
use PHPUnit\Framework\TestCase;

final class RiddlePaymentPolicyTest extends TestCase
{
    public function testPaidModeRequiresAPositiveCostAndActiveValidation(): void
    {
        self::assertTrue(RiddlePaymentPolicy::isPaid(10, 'automatique'));
        self::assertTrue(RiddlePaymentPolicy::isPaid(1, 'MANUELLE'));
        self::assertFalse(RiddlePaymentPolicy::isPaid(0, 'automatique'));
        self::assertFalse(RiddlePaymentPolicy::isPaid(10, 'aucune'));
        self::assertFalse(RiddlePaymentPolicy::isPaid(10, 'Aucune'));
    }
}
