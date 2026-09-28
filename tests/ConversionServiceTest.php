<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionService;
use ChassesAuTresor\Core\Points\PointsRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionService.php';

class ConversionServiceRepository extends PointsRepository
{
    /** @var array<int, mixed> */
    public array $request = [];

    public function __construct()
    {
    }

    public function logConversionRequest(int $userId, int $points, float $amountEur): int
    {
        $this->request = [$userId, $points, $amountEur];

        return 42;
    }

    public function getConversionRequests(
        ?int $userId = null,
        ?string $status = null,
        ?int $limit = null,
        int $offset = 0
    ): array {
        return [
            ['points' => -500, 'amount_eur' => 12.5],
            ['points' => -1000, 'amount_eur' => 25.0],
        ];
    }
}

class ConversionServiceTest extends TestCase
{
    public function testCreateRequestCalculatesAmountAndDebitsPoints(): void
    {
        $repository = new ConversionServiceRepository();
        $service = new ConversionService($repository);

        $this->assertSame(42, $service->createRequest(7, 1500, 25.0));
        $this->assertSame([7, -1500, 37.5], $repository->request);
        $this->assertSame(0, $service->createRequest(0, 1500, 25.0));
    }

    public function testPaidTotalsAreAggregated(): void
    {
        $service = new ConversionService(new ConversionServiceRepository());

        $this->assertSame(['points' => 1500, 'amount' => 37.5], $service->getPaidTotals(7));
    }
}
