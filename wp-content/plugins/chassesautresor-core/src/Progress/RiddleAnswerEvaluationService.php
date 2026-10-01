<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Evaluate an automatic riddle answer against accepted answers and feedback variants.
 */
class RiddleAnswerEvaluationService
{
    /**
     * @param string[] $acceptedAnswers
     * @param array<int, array{texte:string,message:string,casse:bool}> $variants
     * @return array{resultat:string,message:string,index:int}
     */
    public function evaluate(
        string $submittedAnswer,
        array $acceptedAnswers,
        bool $caseSensitive,
        array $variants
    ): array {
        $submittedAnswer = trim($submittedAnswer);
        $comparableAnswer = $this->normalize($submittedAnswer, $caseSensitive);
        foreach ($acceptedAnswers as $acceptedAnswer) {
            if ($comparableAnswer === $this->normalize(trim((string) $acceptedAnswer), $caseSensitive)) {
                return ['resultat' => 'bon', 'message' => '', 'index' => 0];
            }
        }

        foreach ($variants as $index => $variant) {
            $text = trim((string) ($variant['texte'] ?? ''));
            if ($text === '') {
                continue;
            }
            $variantCaseSensitive = (bool) ($variant['casse'] ?? false);
            if ($this->normalize($submittedAnswer, $variantCaseSensitive) === $this->normalize($text, $variantCaseSensitive)) {
                return [
                    'resultat' => 'variante',
                    'message' => trim((string) ($variant['message'] ?? '')),
                    'index' => (int) $index,
                ];
            }
        }

        return ['resultat' => 'faux', 'message' => '', 'index' => 0];
    }

    private function normalize(string $answer, bool $caseSensitive): string
    {
        return $caseSensitive ? $answer : mb_strtolower($answer);
    }
}
