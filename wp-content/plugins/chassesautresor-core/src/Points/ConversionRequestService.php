<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

final class ConversionRequestService
{
    private ConversionService $conversion;

    public function __construct(ConversionService $conversion)
    {
        $this->conversion = $conversion;
    }

    /** @return array{id:int,amount:float,error:string} */
    public function request(int $userId, int $points, int $minimum, int $balance, float $rate): array
    {
        if ($userId <= 0 || $points < $minimum) {
            return ['id' => 0, 'amount' => 0.0, 'error' => 'minimum'];
        }
        if ($points > $balance) {
            return ['id' => 0, 'amount' => 0.0, 'error' => 'balance'];
        }

        $id = $this->conversion->createRequest($userId, $points, $rate);

        return [
            'id' => $id,
            'amount' => round(($points / 1000) * $rate, 2),
            'error' => $id > 0 ? '' : 'storage',
        ];
    }
}
