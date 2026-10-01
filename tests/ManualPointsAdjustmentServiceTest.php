<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ManualPointsAdjustmentService;
use PHPUnit\Framework\TestCase;

final class ManualPointsAdjustmentServiceTest extends TestCase
{
    public function testAdditionProducesPositiveDelta(): void
    {
        $result = (new ManualPointsAdjustmentService())->prepare('ajouter', 50, 10);

        self::assertSame(50, $result['delta']);
        self::assertSame('Ajout manuel de 50 points', $result['reason']);
        self::assertSame('', $result['error']);
    }

    public function testWithdrawalProducesNegativeDelta(): void
    {
        $result = (new ManualPointsAdjustmentService())->prepare('retirer', 50, 100);

        self::assertSame(-50, $result['delta']);
        self::assertSame('Retrait manuel de 50 points', $result['reason']);
    }

    public function testWithdrawalCannotExceedBalance(): void
    {
        $result = (new ManualPointsAdjustmentService())->prepare('retirer', 101, 100);

        self::assertSame('balance', $result['error']);
    }

    public function testInvalidActionIsRejected(): void
    {
        $result = (new ManualPointsAdjustmentService())->prepare('replace', 50, 100);

        self::assertSame('invalid', $result['error']);
    }
}
