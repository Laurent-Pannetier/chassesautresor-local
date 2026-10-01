<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Remove obsolete development messages once, independently from the active theme.
 */
final class LegacySiteMessageCleanup
{
    private const COMPLETED_OPTION = 'cat_removed_test_messages';

    public static function run(): void
    {
        if (get_option(self::COMPLETED_OPTION)) {
            return;
        }

        global $wpdb;

        CoreServiceFactory::siteMessages($wpdb)->removeLegacyTestMessages();
        update_option(self::COMPLETED_OPTION, 1);
    }
}
