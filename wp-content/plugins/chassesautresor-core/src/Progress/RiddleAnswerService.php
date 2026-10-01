<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Normalize the legacy and JSON storage formats used for accepted answers.
 */
class RiddleAnswerService {
    /** @return string[] */
    public function get(int $riddleId): array {
        $raw = get_field('enigme_reponse_bonne', $riddleId);
        if (is_array($raw)) {
            return $this->normalize($raw);
        }
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->normalize($decoded);
        }

        update_field('enigme_reponse_bonne', wp_json_encode([$raw]), $riddleId);
        return [$raw];
    }

    /** @param mixed[] $answers @return string[] */
    private function normalize(array $answers): array {
        return array_values(array_filter(array_map('strval', $answers)));
    }
}
