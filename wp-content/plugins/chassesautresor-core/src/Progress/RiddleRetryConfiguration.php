<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use Closure;

/** Read and normalize the retry delay configured for a riddle. */
final class RiddleRetryConfiguration
{
    public const FIELD_NAME = 'enigme_tentative_delai_secondes';

    private Closure $fieldReader;

    public function __construct(?callable $fieldReader = null)
    {
        $this->fieldReader = $fieldReader !== null
            ? Closure::fromCallable($fieldReader)
            : static fn (string $field, int $riddleId) => get_field($field, $riddleId);
    }

    public function getDelaySeconds(int $riddleId): int
    {
        if ($riddleId <= 0) {
            return 0;
        }

        return max(0, (int) ($this->fieldReader)(self::FIELD_NAME, $riddleId));
    }
}
