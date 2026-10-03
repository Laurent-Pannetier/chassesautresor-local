<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class ColorAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'colors';
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
        $valid = [
            'red', 'orange', 'yellow', 'green', 'blue', 'purple',
            'indigo', 'pink', 'brown', 'grey', 'black', 'white',
        ];
        $tokens = preg_split('/[\s,;>]+/', strtolower(trim($sequence))) ?: [];
        if (array_filter($tokens, static fn (string $token): bool => !in_array($token, $valid, true)) !== []) {
            return null;
        }
        return implode(',', $tokens);
    }
}
