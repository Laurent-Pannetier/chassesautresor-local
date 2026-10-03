<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStepSubmissionRequestPolicy;
use PHPUnit\Framework\TestCase;

final class RiddleStepSubmissionRequestPolicyTest extends TestCase {
    private RiddleStepSubmissionRequestPolicy $policy;

    protected function setUp(): void {
        $this->policy = new RiddleStepSubmissionRequestPolicy();
    }

    public function testAcceptsValidTextAndClickRequests(): void {
        self::assertNull($this->validate());
        self::assertNull($this->validate([
            'answer' => '',
            'answer_required' => false,
            'widget_type' => 'click',
            'allowed_widgets' => ['click'],
        ]));
    }

    /** @dataProvider accessDenialProvider */
    public function testRejectsEveryInvalidAccessContext(array $changes): void {
        self::assertSame(
            RiddleStepSubmissionRequestPolicy::ACCESS_DENIED,
            $this->validate($changes)
        );
    }

    public function accessDenialProvider(): array {
        return [
            'anonymous user' => [['user_id' => 0]],
            'missing riddle' => [['riddle_id' => 0]],
            'foreign post type' => [['riddle_post_type' => 'post']],
            'missing access adapter' => [['access_function_available' => false]],
            'riddle not visible' => [['can_view_riddle' => false]],
            'organizer cannot play' => [['can_modify_riddle' => true]],
        ];
    }

    /** @dataProvider invalidStepProvider */
    public function testRejectsInvalidStepAnswerAndWidgetContexts(array $changes): void {
        self::assertSame(
            RiddleStepSubmissionRequestPolicy::INVALID_STEP,
            $this->validate($changes)
        );
    }

    public function invalidStepProvider(): array {
        return [
            'missing step' => [['step_id' => 0]],
            'foreign step type' => [['step_post_type' => 'post']],
            'step belongs to another riddle' => [['parent_riddle_id' => 10]],
            'unsupported widget' => [['widget_type' => 'click']],
            'missing answer' => [['answer' => '  ']],
        ];
    }

    public function testDailyLimitOnlyAppliesWhenConfigured(): void {
        self::assertFalse($this->policy->hasReachedLimit(0, 100));
        self::assertFalse($this->policy->hasReachedLimit(3, 2));
        self::assertTrue($this->policy->hasReachedLimit(3, 3));
        self::assertTrue($this->policy->hasReachedLimit(3, 4));
    }

    private function validate(array $changes = []): ?string {
        $context = array_merge([
            'user_id' => 4,
            'riddle_id' => 9,
            'step_id' => 12,
            'answer' => 'étoile',
            'answer_required' => true,
            'riddle_post_type' => 'enigme',
            'access_function_available' => true,
            'can_view_riddle' => true,
            'can_modify_riddle' => false,
            'step_post_type' => 'enigme_etape',
            'parent_riddle_id' => 9,
            'widget_type' => 'text',
            'allowed_widgets' => ['text', 'directions', 'colors', 'numbers', 'safe_dial'],
        ], $changes);

        return $this->policy->validate(...array_values($context));
    }
}
