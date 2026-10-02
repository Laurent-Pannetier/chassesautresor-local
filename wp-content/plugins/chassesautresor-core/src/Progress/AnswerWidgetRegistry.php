<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use InvalidArgumentException;

final class AnswerWidgetRegistry {
    /** @var array<string,AnswerWidgetDefinition> */
    private array $definitions = [];

    /** @param AnswerWidgetDefinition[]|null $definitions */
    public function __construct(?array $definitions = null) {
        $defaults = [
            new ClickAnswerWidget(),
            new TextAnswerWidget(),
            new DirectionAnswerWidget(),
            new ColorAnswerWidget(),
            new NumericAnswerWidget(),
            new SafeDialAnswerWidget(),
        ];
        foreach ($definitions ?? $defaults as $definition) {
            $this->definitions[$definition->type()] = $definition;
        }
    }

    /** @param array<string,mixed> $configuration @return array<string,mixed> */
    public function evaluate(string $answer, array $configuration): array {
        $type = (string) ($configuration['type'] ?? '');
        if (!isset($this->definitions[$type])) {
            throw new InvalidArgumentException('Unknown answer widget: ' . $type);
        }

        return $this->definitions[$type]->evaluate($answer, $configuration);
    }

    public function supports(string $type): bool {
        return isset($this->definitions[$type]);
    }
}
