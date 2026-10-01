<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAnswerEvaluationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAnswerEvaluationService.php';

final class RiddleAnswerEvaluationServiceTest extends TestCase
{
    public function testAcceptedAnswerIsTrimmedAndCaseInsensitiveWhenConfigured(): void
    {
        $result = (new RiddleAnswerEvaluationService())->evaluate('  TrÉsor ', ['trésor'], false, []);

        self::assertSame(['resultat' => 'bon', 'message' => '', 'index' => 0], $result);
    }

    public function testAcceptedAnswerCanRequireMatchingCase(): void
    {
        $result = (new RiddleAnswerEvaluationService())->evaluate('Trésor', ['trésor'], true, []);

        self::assertSame(['resultat' => 'faux', 'message' => '', 'index' => 0], $result);
    }

    public function testVariantReturnsItsHistoricalIndexAndMessage(): void
    {
        $result = (new RiddleAnswerEvaluationService())->evaluate(
            'presque',
            ['trésor'],
            false,
            [
                2 => ['texte' => 'Presque', 'message' => 'Vous chauffez.', 'casse' => false],
            ]
        );

        self::assertSame([
            'resultat' => 'variante',
            'message' => 'Vous chauffez.',
            'index' => 2,
        ], $result);
    }

    public function testEmptyVariantsAreIgnored(): void
    {
        $result = (new RiddleAnswerEvaluationService())->evaluate(
            'réponse',
            [],
            false,
            [1 => ['texte' => ' ', 'message' => 'ignored', 'casse' => false]]
        );

        self::assertSame('faux', $result['resultat']);
    }
}
