<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class NumericAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'numbers';
    }

    public function evaluate(string $answer, array $configuration): array {
        $submitted = $this->normalize($answer);
        if ($submitted === null) {
            return ['resultat' => 'faux', 'message' => ''];
        }
        foreach ((array) ($configuration['accepted_sequences'] ?? []) as $sequence) {
            $accepted = $this->normalize((string) $sequence);
            if ($submitted !== '' && $accepted !== null && hash_equals($accepted, $submitted)) {
                return ['resultat' => 'bon', 'message' => ''];
            }
        }

        return ['resultat' => 'faux', 'message' => ''];
    }

    private function normalize(string $sequence): ?string {
        if (preg_match('/^[\d\s,;>\-]*$/', trim($sequence)) !== 1) {
            return null;
        }
        return preg_replace('/\D+/', '', trim($sequence)) ?? '';
    }
}
