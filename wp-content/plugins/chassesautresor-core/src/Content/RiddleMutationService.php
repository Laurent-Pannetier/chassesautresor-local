<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Persist an allowed riddle field and report the required derived-state work.
 */
class RiddleMutationService {
    /**
     * @return array{error:?string,terminal:bool,refresh_state:bool}
     */
    public function apply(
        int $riddleId,
        string $field,
        $value,
        callable $parseDate,
        int $startOfToday
    ): array {
        $policy = new RiddleFieldPolicyService();
        $valid = false;
        $refreshState = false;

        if ($field === 'post_title') {
            $result = wp_update_post([
                'ID' => $riddleId,
                'post_title' => sanitize_text_field($value),
            ], true);

            return [
                'error' => is_wp_error($result) ? 'echec_update_post_title' : null,
                'terminal' => true,
                'refresh_state' => false,
            ];
        }

        if ($field === 'enigme_mode_validation') {
            $valid = (bool) update_field($field, sanitize_text_field($value), $riddleId);
            $refreshState = true;
        }

        if ($field === 'enigme_reponse_bonne') {
            $answers = json_decode(wp_unslash($value), true);
            if (!is_array($answers)) {
                return $this->error('format_invalide');
            }

            $answers = $policy->normalizeAnswers($answers, 'sanitize_text_field');
            $answerError = $policy->getAnswersError($answers);
            if ($answerError !== null) {
                return $this->error($answerError);
            }

            $valid = (bool) update_field($field, wp_json_encode($answers), $riddleId);
            $refreshState = true;
        }

        if ($field === 'enigme_reponse_casse') {
            $valid = (bool) update_field($field, (int) $value, $riddleId);
        }

        $attemptField = $policy->getAttemptStorageField($field);
        if ($attemptField !== null) {
            $valid = update_field($attemptField, (int) $value, $riddleId) !== false;
        }

        if ($field === 'enigme_acces_condition') {
            $condition = sanitize_text_field($value);
            if ($policy->isAllowedManualAccessCondition($condition)) {
                $valid = (bool) update_field($field, $condition, $riddleId);
            }
        }

        if ($field === 'enigme_acces_date') {
            $date = $parseDate(sanitize_text_field($value));
            if (!$date instanceof \DateTimeInterface) {
                return $this->error('format_date_invalide');
            }

            $condition = (string) get_field('enigme_acces_condition', $riddleId);
            if ($policy->shouldResetScheduledAccess($date->getTimestamp(), $startOfToday, $condition)) {
                update_field('enigme_acces_condition', 'immediat', $riddleId);
            }

            $valid = (bool) update_field($field, $date->format('Y-m-d H:i:s'), $riddleId);
            $refreshState = true;
        }

        if ($field === 'enigme_acces_pre_requis') {
            $ids = $policy->normalizePrerequisiteIds($value);
            $valid = (bool) update_field($field, $ids, $riddleId);
            if ($valid) {
                update_field(
                    'enigme_acces_condition',
                    $policy->getAccessConditionForPrerequisites($ids),
                    $riddleId
                );
                $refreshState = true;
            }
        }

        if ($field === 'enigme_style_affichage') {
            $valid = (bool) update_field($field, sanitize_text_field($value), $riddleId);
        }

        if (!$valid) {
            $cleanValue = is_numeric($value) ? (int) $value : sanitize_text_field($value);
            $updated = update_field($field, $cleanValue, $riddleId);
            $storedValue = get_post_meta($riddleId, $field, true);
            $valid = $updated || trim((string) $storedValue) === trim((string) $cleanValue);
        }

        return [
            'error' => $valid ? null : 'echec_mise_a_jour_final',
            'terminal' => false,
            'refresh_state' => $refreshState,
        ];
    }

    /** @return array{error:string,terminal:bool,refresh_state:bool} */
    private function error(string $code): array {
        return ['error' => $code, 'terminal' => false, 'refresh_state' => false];
    }
}
