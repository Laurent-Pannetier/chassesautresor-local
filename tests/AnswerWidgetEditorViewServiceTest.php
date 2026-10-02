<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\AnswerWidgetEditorViewService;
use PHPUnit\Framework\TestCase;

final class AnswerWidgetEditorViewServiceTest extends TestCase {
    public function testDescribesClickAndTextEditorControls(): void {
        $widgets = (new AnswerWidgetEditorViewService())->widgets();

        self::assertSame(['click', 'text', 'directions', 'colors'], array_column($widgets, 'type'));
        self::assertSame('button_label', $widgets[0]['fields'][0]['name']);
        self::assertSame('Continuer', $widgets[0]['fields'][0]['default']);
        self::assertSame(
            ['accepted_answers', 'case_sensitive', 'variants'],
            array_column($widgets[1]['fields'], 'name')
        );
        self::assertSame('checkbox', $widgets[1]['fields'][1]['control']);
        self::assertSame('direction_sequences', $widgets[2]['fields'][0]['name']);
        self::assertSame('color_sequences', $widgets[3]['fields'][0]['name']);
    }
}
