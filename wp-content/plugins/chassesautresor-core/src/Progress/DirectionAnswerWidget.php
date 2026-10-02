<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class DirectionAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'directions';
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
        $aliases = ['O' => 'W', 'NO' => 'NW', 'SO' => 'SW'];
        $tokens = preg_split('/[\s,;>\-]+/', strtoupper(trim($sequence))) ?: [];
        $tokens = array_map(static fn (string $token): string => $aliases[$token] ?? $token, $tokens);
        $valid = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        return implode(',', array_values(array_filter($tokens, static fn (string $token): bool => in_array($token, $valid, true))));
    }
}
