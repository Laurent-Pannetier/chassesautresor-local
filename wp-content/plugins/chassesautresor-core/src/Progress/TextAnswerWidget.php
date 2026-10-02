<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class TextAnswerWidget implements AnswerWidgetDefinition {
    private RiddleAnswerEvaluationService $evaluation;

    public function __construct(?RiddleAnswerEvaluationService $evaluation = null) {
        $this->evaluation = $evaluation ?? new RiddleAnswerEvaluationService();
    }

    public function type(): string {
        return 'text';
    }

    public function evaluate(string $answer, array $configuration): array {
        return $this->evaluation->evaluate(
            $answer,
            (array) ($configuration['accepted_answers'] ?? []),
            (bool) ($configuration['case_sensitive'] ?? false),
            (array) ($configuration['variants'] ?? [])
        );
    }
}
