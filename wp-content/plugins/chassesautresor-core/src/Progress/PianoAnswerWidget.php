<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class PianoAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'piano';
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
        $tokens = preg_split('/[\s,;>]+/', strtoupper(trim($sequence)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $token) {
            if (preg_match('/^[A-G](?:#)?[12]$/', $token) !== 1) {
                return null;
            }
        }

        return implode(',', $tokens);
    }
}
