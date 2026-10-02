<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Adapt legacy ACF storage to one versioned widget configuration. */
final class AnswerWidgetConfigurationService {
    private RiddleAnswerService $riddleAnswers;

    public function __construct(?RiddleAnswerService $riddleAnswers = null) {
        $this->riddleAnswers = $riddleAnswers ?? new RiddleAnswerService();
    }

    /** @return array<string,mixed> */
    public function forStep(int $stepId, ?callable $getField = null): array {
        $getField = $getField ?? 'get_field';
        $type = (string) ($getField('etape_reponse_widget', $stepId) ?: 'click');
        $configuration = $this->base('enigme_etape', $stepId, $type);
        if ($type === 'click') {
            $configuration['button_label'] = (string) $getField('etape_reponse_bouton', $stepId);
            return $configuration;
        }
        if ($type === 'directions') {
            $configuration['accepted_sequences'] = $this->lines(
                (string) $getField('etape_directions_sequences', $stepId)
            );
            return $configuration;
        }
        if ($type === 'colors') {
            $configuration['accepted_sequences'] = $this->lines(
                (string) $getField('etape_color_sequences', $stepId)
            );
            return $configuration;
        }

        $caseSensitive = (bool) $getField('etape_reponse_casse', $stepId);
        $configuration['accepted_answers'] = $this->lines(
            (string) $getField('etape_reponses_texte', $stepId)
        );
        $configuration['case_sensitive'] = $caseSensitive;
        $configuration['variants'] = $this->stepVariants(
            (string) $getField('etape_reponses_variantes', $stepId),
            $caseSensitive
        );
        return $configuration;
    }

    /** @return array<string,mixed> */
    public function forRiddle(int $riddleId, ?callable $getField = null): array {
        $getField = $getField ?? 'get_field';
        $configuration = $this->base('enigme', $riddleId, 'text');
        $configuration['accepted_answers'] = $this->riddleAnswers->get($riddleId);
        $configuration['case_sensitive'] = (int) $getField('enigme_reponse_casse', $riddleId) === 1;
        $configuration['variants'] = [];
        for ($index = 1; $index <= 4; $index++) {
            $configuration['variants'][$index] = [
                'texte' => (string) $getField("texte_{$index}", $riddleId),
                'message' => (string) $getField("message_{$index}", $riddleId),
                'casse' => (int) $getField("respecter_casse_{$index}", $riddleId) === 1,
            ];
        }
        return $configuration;
    }

    /** @return array<string,mixed> */
    private function base(string $targetType, int $targetId, string $type): array {
        return ['version' => 1, 'target_type' => $targetType, 'target_id' => $targetId, 'type' => $type];
    }

    /** @return string[] */
    private function lines(string $value): array {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])));
    }

    /** @return array<int,array<string,mixed>> */
    private function stepVariants(string $value, bool $caseSensitive): array {
        $variants = [];
        foreach (preg_split('/\R/', $value) ?: [] as $index => $line) {
            [$text, $message] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if ($text !== '' && $message !== '') {
                $variants[$index + 1] = ['texte' => $text, 'message' => $message, 'casse' => $caseSensitive];
            }
        }
        return $variants;
    }
}
