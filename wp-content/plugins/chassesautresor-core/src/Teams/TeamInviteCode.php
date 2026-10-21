<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Short shared code. Letters that look like digits are left out so it can be read aloud. */
class TeamInviteCode
{
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const LENGTH = 8;

    public function generate(): string
    {
        $alphabet = self::ALPHABET;
        $last = strlen($alphabet) - 1;
        $code = '';
        for ($index = 0; $index < self::LENGTH; $index++) {
            $code .= $alphabet[random_int(0, $last)];
        }

        return $code;
    }

    public function normalize(string $raw): string
    {
        $compact = preg_replace('/[\s-]+/', '', $raw) ?? '';

        return strtoupper($compact);
    }

    public function isValid(string $code): bool
    {
        return strlen($code) === self::LENGTH && strspn($code, self::ALPHABET) === self::LENGTH;
    }
}
