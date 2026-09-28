<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/**
 * Manage point-to-euro conversion requests.
 */
class ConversionService
{
    private PointsRepository $repository;
    private PointsService $points;

    public function __construct(PointsRepository $repository, ?PointsService $points = null)
    {
        $this->repository = $repository;
        $this->points = $points ?? new PointsService($repository);
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

    /**
     * Update a conversion request and return the resulting business effects.
     *
     * @return array{status:string,refunded_points:int,paid_amount:float}|null
     */
    public function updateStatus(int $requestId, string $action, ?string $date = null): ?array
    {
        $statuses = [
            'regle' => 'paid',
            'annule' => 'cancelled',
            'refuse' => 'refused',
        ];

        if ($requestId <= 0 || !isset($statuses[$action])) {
            return null;
        }

        $status = $statuses[$action];
        $date = $date ?? current_time('mysql');
        $dates = $status === 'paid'
            ? ['settlement_date' => $date]
            : ['cancelled_date' => $date];

        $this->repository->updateRequestStatus($requestId, $status, $dates);
        $request = $this->repository->getRequestById($requestId);
        $refundedPoints = 0;
        $paidAmount = 0.0;

        if ($request !== null && in_array($status, ['cancelled', 'refused'], true)) {
            $refundedPoints = abs((int) $request['points']);
            $reason = sprintf(
                /* translators: %d: number of restored points. */
                __('Restauration de %d points après annulation/refus', 'chassesautresor-com'),
                $refundedPoints
            );
            $this->points->add((int) $request['user_id'], $refundedPoints, $reason, 'admin', $requestId);
        } elseif ($request !== null && $status === 'paid') {
            $paidAmount = (float) $request['amount_eur'];
        }

        return [
            'status' => $status,
            'refunded_points' => $refundedPoints,
            'paid_amount' => $paidAmount,
        ];
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
