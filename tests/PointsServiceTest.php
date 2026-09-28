<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\PointsRepository;
use ChassesAuTresor\Core\Points\PointsService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsService.php';

class PointsServiceRepository extends PointsRepository
{
    public int $balance = 100;

    /** @var array<int, mixed> */
    public array $lastOperation = [];

    public function __construct()
    {
    }

    public function getBalance(int $userId): int
    {
        return $this->balance;
    }

    public function addPoints(
        int $userId,
        int $delta,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): int {
        $this->lastOperation = [$userId, $delta, $reason, $originType, $originId];
        $this->balance += $delta;

        return $this->balance;
    }
}

class PointsServiceTest extends TestCase
{
    public function testServiceAppliesPointOperationRules(): void
    {
        $repository = new PointsServiceRepository();
        $service = new PointsService($repository);

        $this->assertTrue($service->hasEnough(7, 100));
        $this->assertFalse($service->hasEnough(7, -1));

        $service->deduct(7, 25, 'Indice', 'indice', 42);
        $this->assertSame([7, -25, 'Indice', 'indice', 42], $repository->lastOperation);
        $this->assertSame(75, $service->getBalance(7));

        $service->add(7, 10, 'Bonus');
        $this->assertSame([7, 10, 'Bonus', 'admin', null], $repository->lastOperation);
        $this->assertSame(85, $service->getBalance(7));
    }

    public function testServiceIgnoresInvalidUsersAndAmounts(): void
    {
        $repository = new PointsServiceRepository();
        $service = new PointsService($repository);

        $service->deduct(0, 10);
        $service->add(7, 0);

        $this->assertSame([], $repository->lastOperation);
        $this->assertSame(0, $service->getBalance(0));
    }
}
