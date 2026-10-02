<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class NumericAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'numbers';
    }

    public function evaluate(string $answer, array $configuration): array {
        $submitted = $this->normalize($answer);
        foreach ((array) ($configuration['accepted_sequences'] ?? []) as $sequence) {
            if ($submitted !== '' && hash_equals($this->normalize((string) $sequence), $submitted)) {
                return ['resultat' => 'bon', 'message' => ''];
            }
        }

        return ['resultat' => 'faux', 'message' => ''];
    }

    private function normalize(string $sequence): string {
        return preg_replace('/\D+/', '', trim($sequence)) ?? '';
    }
}
