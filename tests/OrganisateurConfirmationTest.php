<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OrganisateurConfirmationTest extends TestCase
{
    public function testOrganizerRequestFunctionsAreOwnedByCore(): void
    {
        $core = (string) file_get_contents(
            __DIR__
                . '/../wp-content/plugins/chassesautresor-core/src/Relationships/organizer-request-functions.php'
        );
        $theme = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/inc/organisateur-functions.php'
        );

        foreach ([
            'cat_clear_organisateur_request',
            'cat_get_organisateur_request_status',
            'envoyer_email_confirmation_organisateur',
            'lancer_demande_organisateur',
            'renvoyer_email_confirmation_organisateur',
        ] as $function) {
            self::assertStringContainsString('function ' . $function, $core);
            self::assertStringNotContainsString('function ' . $function, $theme);
        }
    }

    public function testLegacyConfirmationTemplateDoesNotMutateBusinessState(): void
    {
        $template = (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/templates/page-confirmation-organisateur.php'
        );

        self::assertStringNotContainsString('confirmer_demande_organisateur', $template);
        self::assertStringNotContainsString('$_GET', $template);
        self::assertStringContainsString('esc_html_e', $template);
    }
}
