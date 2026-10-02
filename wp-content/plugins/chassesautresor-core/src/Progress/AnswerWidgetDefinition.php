<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

interface AnswerWidgetDefinition {
    public function type(): string;

    /** @param array<string,mixed> $configuration @return array<string,mixed> */
    public function evaluate(string $answer, array $configuration): array;
}
