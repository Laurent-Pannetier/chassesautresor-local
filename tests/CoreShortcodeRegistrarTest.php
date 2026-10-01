<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\CoreShortcodeRegistrar;
use PHPUnit\Framework\TestCase;

final class CoreShortcodeRegistrarTest extends TestCase {
    public function testRegistersFunctionalShortcodes(): void {
        $shortcodes = [];
        CoreShortcodeRegistrar::register(
            static function (...$arguments) use (&$shortcodes): void {
                $shortcodes[] = $arguments;
            }
        );

        self::assertSame([
            ['afficher_points_utilisateur', [CoreShortcodeRegistrar::class, 'points']],
            ['formulaire_reponse_manuelle', [CoreShortcodeRegistrar::class, 'manualAnswer']],
        ], $shortcodes);
    }
}
