<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Decide whether an enigma charges points for an attempt. */
final class RiddlePaymentPolicy
{
    public static function isPaid(int $cost, string $mode): bool
    {
        return $cost > 0 && strtolower($mode) !== 'aucune';
    }
}
