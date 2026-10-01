<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Normalize statistics periods and calculate their date boundaries.
 */
class StatisticsPeriodService
{
    public const PERIODS = ['jour', 'semaine', 'mois', 'total'];

    public function normalize(string $period): string
    {
        return in_array($period, self::PERIODS, true) ? $period : 'total';
    }

    /** @return array{?string,?string} */
    public function range(string $period, ?DateTimeInterface $now = null): array
    {
        $period = $this->normalize($period);
        if ($period === 'total') {
            return [null, null];
        }

        $timezone = new DateTimeZone('Europe/Paris');
        $current = $now
            ? new DateTimeImmutable($now->format('Y-m-d H:i:s'), $now->getTimezone())
            : new DateTimeImmutable('now', $timezone);
        $current = $current->setTimezone($timezone);

        if ($period === 'jour') {
            $start = $current->setTime(0, 0);
        } elseif ($period === 'semaine') {
            $start = $current->modify('monday this week')->setTime(0, 0);
        } else {
            $start = $current->modify('first day of this month')->setTime(0, 0);
        }

        return [$start->format('Y-m-d H:i:s'), $current->format('Y-m-d H:i:s')];
    }
}
