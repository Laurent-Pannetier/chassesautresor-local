<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/important-messages.php';

final class HuntStateNoticeTest extends TestCase
{
    public function testRecognizesStoredHuntStateNotices(): void
    {
        $accepted = 'Votre demande de validation pour la chasse « Antre de feu » a été acceptée.';

        self::assertTrue(cat_is_hunt_state_notice($accepted));
        self::assertTrue(cat_is_hunt_state_notice('Votre chasse « Antre de feu » a été bannie.'));
        self::assertTrue(cat_is_hunt_state_notice('Votre chasse a été supprimée.'));
        self::assertTrue(cat_is_hunt_state_notice('', 'correction_chasse_12'));
        self::assertTrue(cat_is_hunt_state_notice('', 'correction_info_chasse_12'));
        self::assertFalse(cat_is_hunt_state_notice('Points mis à jour avec succès.'));
        self::assertFalse(cat_is_hunt_state_notice(
            'Votre demande de résolution de l\'énigme est en cours de traitement.'
        ));
    }

    public function testHuntStateNoticesStayVisibleWhenPlatformModeIsUnknown(): void
    {
        self::assertFalse(cat_hunt_state_notices_are_hidden());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHuntStateNoticesAreHiddenOutsidePlatformMode(): void
    {
        function cat_is_platform_mode(): bool
        {
            return false;
        }

        require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Messages/important-messages.php';

        self::assertTrue(cat_hunt_state_notices_are_hidden());
    }
}
