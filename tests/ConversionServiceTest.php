<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionService;
use ChassesAuTresor\Core\Points\PointsRepository;
use ChassesAuTresor\Core\Points\PointsService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/ConversionService.php';

class ConversionServiceRepository extends PointsRepository
{
    /** @var array<int, mixed> */
    public array $request = [];
    public string $status = '';

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

    public function updateRequestStatus(int $id, string $status, array $dates = []): void
    {
        $this->status = $status;
    }

    public function getRequestById(int $id): ?array
    {
        return ['user_id' => 7, 'points' => -500, 'amount_eur' => 12.5];
    }
}

class ConversionPointsRecorder extends PointsService
{
    /** @var array<int, mixed> */
    public array $operation = [];

    public function __construct()
    {
    }

    public function add(
        int $userId,
        int $amount,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): void {
        $this->operation = [$userId, $amount, $reason, $originType, $originId];
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

    public function testCancelledRequestRestoresPoints(): void
    {
        $repository = new ConversionServiceRepository();
        $points = new ConversionPointsRecorder();
        $service = new ConversionService($repository, $points);

        $result = $service->updateStatus(12, 'annule', '2023-01-01 00:00:00');

        $this->assertSame('cancelled', $repository->status);
        $this->assertSame(500, $result['refunded_points']);
        $this->assertSame([7, 500, 'Restauration de 500 points après annulation/refus', 'admin', 12], $points->operation);
    }
}
