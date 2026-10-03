<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

final class SafeDialAnswerWidget implements AnswerWidgetDefinition {
    public function type(): string {
        return 'safe_dial';
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
        if (preg_match('/^(?:[HA]\s*\d{1,2})(?:[\s,;>]+[HA]\s*\d{1,2})*$/i', trim($sequence)) !== 1) {
            return null;
        }
        preg_match_all('/(?:^|[\s,;>]+)(H|A)\s*(\d{1,2})(?=$|[\s,;>]+)/i', trim($sequence), $matches);
        $movements = [];
        foreach ($matches[1] ?? [] as $index => $direction) {
            $value = (int) ($matches[2][$index] ?? -1);
            if ($value >= 0 && $value <= 99) {
                $movements[] = strtoupper((string) $direction) . $value;
            }
        }

        return implode(',', $movements);
    }
}
