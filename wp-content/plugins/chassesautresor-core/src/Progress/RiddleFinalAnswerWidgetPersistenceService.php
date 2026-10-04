<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleFieldPolicyService;

/**
 * Map a validated final-answer widget configuration to enigme ACF storage.
 * Text answers keep the legacy fields so existing enigmas stay compatible.
 */
final class RiddleFinalAnswerWidgetPersistenceService {
    private RiddleAnswerService $answers;
    private RiddleFieldPolicyService $policy;

    public function __construct(
        ?RiddleAnswerService $answers = null,
        ?RiddleFieldPolicyService $policy = null
    ) {
        $this->answers = $answers ?? new RiddleAnswerService();
        $this->policy = $policy ?? new RiddleFieldPolicyService();
    }

    /** @param array<string,mixed> $configuration @return true|\WP_Error */
    public function persist(int $riddleId, array $configuration) {
        $type = (string) ($configuration['widget'] ?? 'text');
        update_field('enigme_reponse_widget', $type, $riddleId);

        if ($type === 'text') {
            return $this->persistText($riddleId, $configuration);
        }

        update_field(
            'enigme_directions_sequences',
            (string) ($configuration['direction_sequences'] ?? ''),
            $riddleId
        );
        update_field(
            'enigme_color_sequences',
            (string) ($configuration['color_sequences'] ?? ''),
            $riddleId
        );
        update_field(
            'enigme_number_sequences',
            (string) ($configuration['number_sequences'] ?? ''),
            $riddleId
        );
        update_field(
            'enigme_safe_dial_sequences',
            (string) ($configuration['safe_dial_sequences'] ?? ''),
            $riddleId
        );
        update_field(
            'enigme_piano_sequences',
            (string) ($configuration['piano_sequences'] ?? ''),
            $riddleId
        );
        update_field(
            'enigme_gps_coordinates',
            (string) ($configuration['gps_coordinates'] ?? ''),
            $riddleId
        );
        update_field(
            'enigme_gps_tolerance',
            (string) ($configuration['gps_tolerance'] ?? '25'),
            $riddleId
        );

        return true;
    }

    /**
     * Build the editor payload from the current enigme storage.
     *
     * @return array<string,mixed>
     */
    public function loadEditorValues(int $riddleId, ?callable $getField = null): array {
        $getField = $getField ?? 'get_field';
        $type = (string) ($getField('enigme_reponse_widget', $riddleId) ?: 'text');
        if (!in_array($type, ['text', 'directions', 'colors', 'numbers', 'safe_dial', 'piano', 'gps'], true)) {
            $type = 'text';
        }

        $caseSensitive = (int) $getField('enigme_reponse_casse', $riddleId) === 1;
        $variants = [];
        for ($index = 1; $index <= 4; $index++) {
            $text = trim((string) $getField("texte_{$index}", $riddleId));
            $message = trim((string) $getField("message_{$index}", $riddleId));
            if ($text !== '' && $message !== '') {
                $variants[] = $text . ' | ' . $message;
            }
        }

        return [
            'widget' => $type,
            'accepted_answers' => implode("\n", $this->answers->get($riddleId)),
            'case_sensitive' => $caseSensitive,
            'variants' => implode("\n", $variants),
            'direction_sequences' => (string) $getField('enigme_directions_sequences', $riddleId),
            'color_sequences' => (string) $getField('enigme_color_sequences', $riddleId),
            'number_sequences' => (string) $getField('enigme_number_sequences', $riddleId),
            'safe_dial_sequences' => (string) $getField('enigme_safe_dial_sequences', $riddleId),
            'piano_sequences' => (string) $getField('enigme_piano_sequences', $riddleId),
            'gps_coordinates' => (string) $getField('enigme_gps_coordinates', $riddleId),
            'gps_tolerance' => (string) ($getField('enigme_gps_tolerance', $riddleId) ?: '25'),
        ];
    }

    public function isComplete(int $riddleId, ?callable $getField = null): bool {
        $values = $this->loadEditorValues($riddleId, $getField);
        $validated = (new AnswerWidgetValidationService())->validate([
            'widget' => $values['widget'],
            'accepted_answers' => $values['accepted_answers'],
            'case_sensitive' => $values['case_sensitive'] ? 1 : 0,
            'variants' => $values['variants'],
            'direction_sequences' => $values['direction_sequences'],
            'color_sequences' => $values['color_sequences'],
            'number_sequences' => $values['number_sequences'],
            'safe_dial_sequences' => $values['safe_dial_sequences'],
            'piano_sequences' => $values['piano_sequences'],
            'gps_coordinates' => $values['gps_coordinates'],
            'gps_tolerance' => $values['gps_tolerance'],
        ]);

        if (is_wp_error($validated)) {
            return false;
        }

        if (($values['widget'] ?? '') !== 'text') {
            return true;
        }

        $answers = $this->lines((string) $validated['accepted_answers']);
        return $this->policy->getAnswersError($answers) === null && $answers !== [];
    }

    /** @param array<string,mixed> $configuration @return true|\WP_Error */
    private function persistText(int $riddleId, array $configuration) {
        $answers = $this->lines((string) ($configuration['accepted_answers'] ?? ''));
        $answerError = $this->policy->getAnswersError($answers);
        if ($answerError !== null) {
            return new \WP_Error(
                $answerError,
                __('Les réponses acceptées sont invalides.', 'chassesautresor-com')
            );
        }

        update_field('enigme_reponse_bonne', wp_json_encode($answers), $riddleId);
        update_field('enigme_reponse_casse', !empty($configuration['case_sensitive']) ? 1 : 0, $riddleId);

        $variantLines = $this->lines((string) ($configuration['variants'] ?? ''));
        $caseSensitive = !empty($configuration['case_sensitive']);
        for ($index = 1; $index <= 4; $index++) {
            $line = $variantLines[$index - 1] ?? '';
            [$text, $message] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            update_field("texte_{$index}", $text, $riddleId);
            update_field("message_{$index}", $message, $riddleId);
            update_field("respecter_casse_{$index}", $caseSensitive ? 1 : 0, $riddleId);
        }

        return true;
    }

    /** @return string[] */
    private function lines(string $value): array {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])));
    }
}
