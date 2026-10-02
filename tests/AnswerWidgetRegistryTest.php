<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\AnswerWidgetRegistry;
use PHPUnit\Framework\TestCase;

final class AnswerWidgetRegistryTest extends TestCase {
    public function testEvaluatesClickAndTextWidgetsThroughOneContract(): void {
        $registry = new AnswerWidgetRegistry();

        self::assertSame('bon', $registry->evaluate('', ['type' => 'click'])['resultat']);
        self::assertSame('bon', $registry->evaluate('ÉTOILE', [
            'type' => 'text',
            'accepted_answers' => ['etoile'],
            'case_sensitive' => false,
            'variants' => [],
        ])['resultat']);
        self::assertSame('variante', $registry->evaluate('astre', [
            'type' => 'text',
            'accepted_answers' => ['etoile'],
            'case_sensitive' => false,
            'variants' => [1 => ['texte' => 'astre', 'message' => 'Proche', 'casse' => false]],
        ])['resultat']);
    }

    public function testRejectsUnknownWidget(): void {
        $this->expectException(\InvalidArgumentException::class);
        (new AnswerWidgetRegistry())->evaluate('', ['type' => 'unknown']);
    }

    public function testEvaluatesFrenchAndEnglishDirectionAliases(): void {
        $registry = new AnswerWidgetRegistry();
        $configuration = ['type' => 'directions', 'accepted_sequences' => ['N, NE, E, SO, O']];

        self::assertSame('bon', $registry->evaluate('N,NE,E,SW,W', $configuration)['resultat']);
        self::assertSame('faux', $registry->evaluate('N,NE,E,W,SW', $configuration)['resultat']);
    }

    public function testEvaluatesColorSequence(): void {
        $configuration = ['type' => 'colors', 'accepted_sequences' => ['red,blue,green']];
        self::assertSame('bon', (new AnswerWidgetRegistry())->evaluate('red,blue,green', $configuration)['resultat']);
    }
}
