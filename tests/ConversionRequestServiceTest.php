<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionRequestService;
use ChassesAuTresor\Core\Points\ConversionService;
use PHPUnit\Framework\TestCase;

final class ConversionRequestRecorder extends ConversionService
{
    /** @var array<int, int|float> */
    public array $request = [];

    public function __construct()
    {
    }

    public function createRequest(int $userId, int $points, float $rate): int
    {
        $this->request = [$userId, $points, $rate];

        return 37;
    }
}

final class ConversionRequestServiceTest extends TestCase
{
    public function testRequestRejectsAmountBelowMinimum(): void
    {
        $service = new ConversionRequestService(new ConversionRequestRecorder());

        self::assertSame('minimum', $service->request(4, 499, 500, 2000, 85.0)['error']);
    }

    public function testRequestRejectsInsufficientBalance(): void
    {
        $service = new ConversionRequestService(new ConversionRequestRecorder());

        self::assertSame('balance', $service->request(4, 1000, 500, 999, 85.0)['error']);
    }

    public function testRequestPersistsConversionAndCalculatesAmount(): void
    {
        $conversion = new ConversionRequestRecorder();
        $service = new ConversionRequestService($conversion);

        $result = $service->request(4, 1500, 500, 2000, 85.0);

        self::assertSame(['id' => 37, 'amount' => 127.5, 'error' => ''], $result);
        self::assertSame([4, 1500, 85.0], $conversion->request);
    }
}
