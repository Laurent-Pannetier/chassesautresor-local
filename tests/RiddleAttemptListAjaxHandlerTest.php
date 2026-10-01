<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAttemptListAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleAttemptListAjaxHandler.php';

final class RiddleAttemptListAjaxHandlerTest extends TestCase {
    public function testAcceptsLazyCallbacksBeforeTheirDependenciesAreNeeded(): void {
        RiddleAttemptListAjaxHandler::configure(
            static fn (int $id): bool => $id > 0,
            static fn (): array => [],
            static fn (): int => 0,
            static fn (): string => ''
        );
        $this->addToAssertionCount(1);
    }

    public function testRegistersAuthenticatedEndpointOnly(): void {
        $hooks = [];
        RiddleAttemptListAjaxHandler::register(static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        });

        $this->assertSame([
            'wp_ajax_lister_tentatives_enigme' => [RiddleAttemptListAjaxHandler::class, 'handle'],
        ], $hooks);
    }
}
