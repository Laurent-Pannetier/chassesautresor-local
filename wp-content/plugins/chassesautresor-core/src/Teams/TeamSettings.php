<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Site-wide cap on the number of active members in a team. */
final class TeamSettings
{
    public const OPTION = 'cat_equipe_taille_max';

    public const DEFAULT = 5;

    public const MIN = 1;

    public const CAP = 20;

    public function __construct(private readonly ?int $forced = null)
    {
    }

    public function max(): int
    {
        if ($this->forced !== null) {
            return $this->bound($this->forced);
        }

        $stored = self::DEFAULT;
        if (function_exists('get_option')) {
            $stored = (int) get_option(self::OPTION, self::DEFAULT);
        }

        return $this->bound($stored);
    }

    public function accept(int $value): ?int
    {
        if ($value < self::MIN || $value > self::CAP) {
            return null;
        }

        return $value;
    }

    private function bound(int $value): int
    {
        if ($value < self::MIN) {
            return self::MIN;
        }
        if ($value > self::CAP) {
            return self::CAP;
        }

        return $value;
    }
}
