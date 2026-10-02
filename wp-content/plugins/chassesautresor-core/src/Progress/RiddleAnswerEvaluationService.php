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
        $answer = str_replace(
            ["\u{2018}", "\u{2019}", "\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}"],
            ["'", "'", '-', '-', '-', '-', '-'],
            trim($answer)
        );
        $answer = preg_replace('/\s+/u', ' ', $answer) ?? $answer;
        if ($caseSensitive) {
            return $answer;
        }

        $answer = function_exists('remove_accents') ? remove_accents($answer) : $answer;
        $answer = strtr($answer, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'ç' => 'c', 'Ç' => 'C',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'î' => 'i', 'ï' => 'i', 'Î' => 'I', 'Ï' => 'I',
            'ô' => 'o', 'ö' => 'o', 'Ô' => 'O', 'Ö' => 'O',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        ]);
        return mb_strtolower($answer);
    }
}
