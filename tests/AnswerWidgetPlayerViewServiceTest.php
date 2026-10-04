<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\AnswerWidgetPlayerViewService;
use PHPUnit\Framework\TestCase;

final class AnswerWidgetPlayerViewServiceTest extends TestCase {
    public function testBuildsClickViewWithoutAttemptLimit(): void {
        $view = (new AnswerWidgetPlayerViewService())->build([
            'type' => 'click',
            'button_label' => 'Open',
        ], 1, 1);

        self::assertSame('confirmer_etape_enigme', $view['action']);
        self::assertSame('riddle_step_click', $view['nonce_action']);
        self::assertSame('Open', $view['button_label']);
        self::assertFalse($view['limit_reached']);
    }

    public function testBuildsLimitedTextView(): void {
        $view = (new AnswerWidgetPlayerViewService())->build(['type' => 'text'], 2, 2);

        self::assertSame('soumettre_reponse_etape', $view['action']);
        self::assertSame('reponse', $view['input_name']);
        self::assertTrue($view['limit_reached']);
    }

    public function testBuildsDirectionPadView(): void {
        $view = (new AnswerWidgetPlayerViewService())->build(['type' => 'directions'], 3, 1);

        self::assertSame('directions', $view['type']);
        self::assertSame('soumettre_reponse_etape', $view['action']);
        self::assertFalse($view['limit_reached']);
    }

    public function testBuildsNumericAndSafeDialViews(): void {
        $service = new AnswerWidgetPlayerViewService();

        self::assertSame('numbers', $service->build(['type' => 'numbers'])['type']);
        self::assertSame('safe_dial', $service->build(['type' => 'safe_dial'])['type']);
        self::assertSame('piano', $service->build(['type' => 'piano'])['type']);
        self::assertSame(
            'riddle-step-text-form riddle-step-piano-form',
            $service->build(['type' => 'piano'])['form_class']
        );
    }
}
