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
        'piano' => 'piano_sequences',
    ];

    /**
     * Reason the configuration cannot be published, or null when it is valid.
     *
     * @param array<string,mixed> $configuration
     */
    public function issueMessage(array $configuration): ?string {
        $type = (string) ($configuration['widget'] ?? '');
        if (!in_array($type, ['click', 'text', 'directions', 'colors', 'numbers', 'safe_dial', 'piano', 'gps'], true)) {
            return __('Choisissez un mode de réponse.', 'chassesautresor-com');
        }

        if ($type === 'click') {
            return trim((string) ($configuration['button_label'] ?? '')) !== ''
                ? null
                : __('Indiquez le libellé du bouton.', 'chassesautresor-com');
        }

        if ($type === 'text') {
            $answers = $this->lines((string) ($configuration['accepted_answers'] ?? ''));
            if ($answers === []) {
                return __('Indiquez au moins une réponse acceptée, une par ligne.', 'chassesautresor-com');
            }
            $variants = $this->lines((string) ($configuration['variants'] ?? ''));
            if (!$this->hasValidVariants($variants)) {
                return __(
                    'Chaque variante doit contenir une réponse et un message, séparés par |.',
                    'chassesautresor-com'
                );
            }

            return null;
        }

        if ($type === 'gps') {
            $coordinates = trim((string) ($configuration['gps_coordinates'] ?? ''));
            $tolerance = filter_var($configuration['gps_tolerance'] ?? null, FILTER_VALIDATE_FLOAT);
            $invalidTolerance = $tolerance === false || $tolerance < 1 || $tolerance > 100000;
            if (!$this->isValidCoordinates($coordinates) || $invalidTolerance) {
                return __(
                    'Indiquez une latitude et une longitude valides, et une tolérance entre 1 et 100000 mètres.',
                    'chassesautresor-com'
                );
            }

            return null;
        }

        $sequences = $this->lines((string) ($configuration[self::SEQUENCE_FIELDS[$type]] ?? ''));
        if ($sequences === []) {
            return __('Indiquez au moins un code accepté, un par ligne.', 'chassesautresor-com');
        }
        foreach ($sequences as $sequence) {
            if (!$this->isValidSequence($type, $sequence)) {
                return __(
                    'Un code ne respecte pas le format attendu pour ce mode de réponse.',
                    'chassesautresor-com'
                );
            }
        }

        return null;
    }

    /** @param array<string,mixed> $configuration @return array<string,mixed>|\WP_Error */
    public function validate(array $configuration) {
        $issue = $this->issueMessage($configuration);
        if ($issue !== null) {
            return new \WP_Error('invalid_answer_widget', $issue);
        }

        $type = (string) $configuration['widget'];
        if ($type === 'text') {
            $configuration['accepted_answers'] = implode(
                "\n",
                $this->lines((string) ($configuration['accepted_answers'] ?? ''))
            );
            $configuration['variants'] = implode(
                "\n",
                $this->lines((string) ($configuration['variants'] ?? ''))
            );

            return $configuration;
        }

        if ($type === 'gps') {
            $configuration['gps_coordinates'] = trim((string) ($configuration['gps_coordinates'] ?? ''));
            $configuration['gps_tolerance'] = (string) filter_var(
                $configuration['gps_tolerance'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            return $configuration;
        }

        if (isset(self::SEQUENCE_FIELDS[$type])) {
            $field = self::SEQUENCE_FIELDS[$type];
            $configuration[$field] = implode("\n", $this->lines((string) ($configuration[$field] ?? '')));
        }

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
        if ($type === 'piano') {
            $tokens = preg_split('/[\s,;>]+/', strtoupper($sequence), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            return $tokens !== [] && count(array_filter(
                $tokens,
                static fn (string $token): bool => preg_match('/^[A-G](?:#)?[12]$/', $token) !== 1
            )) === 0;
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
}
