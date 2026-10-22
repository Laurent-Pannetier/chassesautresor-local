<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthScreenMarkupTest extends TestCase
{
    public function testHuntImageStaysInsideASixteenByNineFrame(): void
    {
        $scss = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_auth.scss'
        );
        $template = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/templates/page-connexion.php'
        );

        $screen = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/auth-screen.php'
        );

        self::assertStringContainsString('aspect-ratio: 16 / 9', $scss);
        self::assertStringContainsString('auth-card__figure', $template);
        self::assertStringContainsString('Continuer avec Discord', $screen);
        self::assertStringContainsString('Continuer avec Google', $screen);
        self::assertStringContainsString('Continuer avec un e-mail', $template);
        self::assertSame(2, substr_count($template, 'cta_auth_provider_buttons'));
    }
}
