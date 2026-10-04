<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Validate and normalize an intermediate-step widget before anything is persisted. */
final class AnswerWidgetValidationService {
    private const SEQUENCE_FIELDS = [
        'directions' => 'direction_sequences',
        'colors' => 'color_sequences',
        'numbers' => 'number_sequences',
        'safe_dial' => 'safe_dial_sequences',
    ];

    /** @param array<string,mixed> $configuration @return array<string,mixed>|\WP_Error */
    public function validate(array $configuration) {
        $type = (string) ($configuration['widget'] ?? '');
        if (!in_array($type, ['click', 'text', 'directions', 'colors', 'numbers', 'safe_dial', 'gps'], true)) {
            return $this->error();
        }

        if ($type === 'click') {
            return trim((string) ($configuration['button_label'] ?? '')) !== ''
                ? $configuration
                : $this->error();
        }

        if ($type === 'text') {
            $answers = $this->lines((string) ($configuration['accepted_answers'] ?? ''));
            $variants = $this->lines((string) ($configuration['variants'] ?? ''));
            if ($answers === [] || !$this->hasValidVariants($variants)) {
                return $this->error();
            }
            $configuration['accepted_answers'] = implode("\n", $answers);
            $configuration['variants'] = implode("\n", $variants);
            return $configuration;
        }

        if ($type === 'gps') {
            $coordinates = trim((string) ($configuration['gps_coordinates'] ?? ''));
            $tolerance = filter_var($configuration['gps_tolerance'] ?? null, FILTER_VALIDATE_FLOAT);
            $invalidTolerance = $tolerance === false || $tolerance < 1 || $tolerance > 100000;
            if (!$this->isValidCoordinates($coordinates) || $invalidTolerance) {
                return $this->error();
            }
            $configuration['gps_coordinates'] = $coordinates;
            $configuration['gps_tolerance'] = (string) $tolerance;
            return $configuration;
        }

        $field = self::SEQUENCE_FIELDS[$type];
        $sequences = $this->lines((string) ($configuration[$field] ?? ''));
        if ($sequences === []) {
            return $this->error();
        }
        foreach ($sequences as $sequence) {
            if (!$this->isValidSequence($type, $sequence)) {
                return $this->error();
            }
        }
        $configuration[$field] = implode("\n", $sequences);

        return $configuration;
    }

    /** @return string[] */
    private function lines(string $value): array {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])));
    }

    private function isValidSequence(string $type, string $sequence): bool {
        if ($type === 'numbers') {
            return preg_match('/^[\d\s,;>\-]+$/', $sequence) === 1
                && preg_match('/\d/', $sequence) === 1;
        }
        if ($type === 'safe_dial') {
            return preg_match(
                '/^(?:[HA]\s*\d{1,2})(?:[\s,;>]+[HA]\s*\d{1,2})*$/i',
                $sequence
            ) === 1;
        }

        $tokens = preg_split('/[\s,;>\-]+/', strtoupper($sequence), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === []) {
            return false;
        }
        $valid = $type === 'directions'
            ? ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW', 'O', 'NO', 'SO']
            : [
                'RED', 'ORANGE', 'YELLOW', 'GREEN', 'BLUE', 'PURPLE',
                'INDIGO', 'PINK', 'BROWN', 'GREY', 'BLACK', 'WHITE',
            ];

        return count(array_diff($tokens, $valid)) === 0;
    }

    /** @param string[] $variants */
    private function hasValidVariants(array $variants): bool {
        foreach ($variants as $variant) {
            [$answer, $message] = array_pad(array_map('trim', explode('|', $variant, 2)), 2, '');
            if ($answer === '' || $message === '') {
                return false;
            }
        }

        return true;
    }

    private function isValidCoordinates(string $value): bool {
        if (preg_match('/^\s*(-?\d+(?:[.,]\d+)?)\s*[;|\s]\s*(-?\d+(?:[.,]\d+)?)\s*$/', $value, $matches) !== 1) {
            return false;
        }

        return abs((float) str_replace(',', '.', $matches[1])) <= 90
            && abs((float) str_replace(',', '.', $matches[2])) <= 180;
    }

    private function error(): \WP_Error {
        return new \WP_Error(
            'invalid_answer_widget',
            __('Mode de réponse invalide.', 'chassesautresor-com')
        );
    }
}
