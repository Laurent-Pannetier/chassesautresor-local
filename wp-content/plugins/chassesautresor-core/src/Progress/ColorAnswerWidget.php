<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class ColorAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'colors';
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
        $valid = ['red', 'orange', 'yellow', 'green', 'blue', 'purple'];
        $tokens = preg_split('/[\s,;>]+/', strtolower(trim($sequence))) ?: [];
        return implode(',', array_values(array_filter($tokens, static fn (string $token): bool => in_array($token, $valid, true))));
    }
}
