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
        $configuration = ['type' => 'colors', 'accepted_sequences' => ['red,pink,brown,grey,black,white']];
        self::assertSame(
            'bon',
            (new AnswerWidgetRegistry())->evaluate('red,pink,brown,grey,black,white', $configuration)['resultat']
        );
    }

    public function testEvaluatesNumericSequenceAndPreservesLeadingZeroes(): void {
        $registry = new AnswerWidgetRegistry();
        $configuration = ['type' => 'numbers', 'accepted_sequences' => ['0129']];

        self::assertSame('bon', $registry->evaluate('0 1 2 9', $configuration)['resultat']);
        self::assertSame('faux', $registry->evaluate('129', $configuration)['resultat']);
    }

    public function testEvaluatesSafeDialDirectionAndValues(): void {
        $registry = new AnswerWidgetRegistry();
        $configuration = ['type' => 'safe_dial', 'accepted_sequences' => ['H11 A51']];

        self::assertSame('bon', $registry->evaluate('h11,a51', $configuration)['resultat']);
        self::assertSame('faux', $registry->evaluate('A11 H51', $configuration)['resultat']);
        self::assertSame('faux', $registry->evaluate('H100 A51', $configuration)['resultat']);
    }
}
