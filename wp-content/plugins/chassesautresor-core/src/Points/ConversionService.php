<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/**
 * Manage point-to-euro conversion requests.
 */
class ConversionService
{
    private PointsRepository $repository;

    public function __construct(PointsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createRequest(int $userId, int $points, float $rate): int
    {
        if ($userId <= 0 || $points <= 0 || $rate < 0) {
            return 0;
        }

        $amount = round(($points / 1000) * $rate, 2);

        return $this->repository->logConversionRequest($userId, -$points, $amount);
    }

    /** @return array<int, array<string, mixed>> */
    public function getRequests(
        ?int $userId = null,
        ?string $status = null,
        ?int $limit = null,
        int $offset = 0
    ): array {
        return $this->repository->getConversionRequests($userId, $status, $limit, $offset);
    }

    public function countRequests(?int $userId = null, ?string $status = null): int
    {
        return $this->repository->countConversionRequests($userId, $status);
    }

    /** @return array{points:int,amount:float} */
    public function getPaidTotals(?int $userId = null): array
    {
        $points = 0;
        $amount = 0.0;

        foreach ($this->getRequests($userId, 'paid') as $request) {
            $points += abs((int) $request['points']);
            $amount += (float) $request['amount_eur'];
        }

        return ['points' => $points, 'amount' => $amount];
    }
}
