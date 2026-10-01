<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\HuntValidationMessageHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Messages/HuntValidationMessageHookHandler.php';

final class MyAccountMessagesTest extends TestCase
{
    public function testValidationMessageHookIsOwnedByCore(): void
    {
        $actions = [];
        HuntValidationMessageHookHandler::register(
            static function (...$arguments) use (&$actions): void {
                $actions[] = $arguments;
            }
        );

        self::assertSame('template_redirect', $actions[0][0]);
        self::assertSame([HuntValidationMessageHookHandler::class, 'handle'], $actions[0][1]);
    }
}
