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
}
