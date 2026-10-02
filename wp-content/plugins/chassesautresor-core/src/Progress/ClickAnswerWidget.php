<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class ClickAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'click';
    }

    public function evaluate(string $answer, array $configuration): array {
        return ['resultat' => 'bon', 'message' => ''];
    }
}
