<?php
/**
 * Plugin Name: Chasses au Tresor Core
 * Description: Fonctionnalites metier de chassesautresor.com.
 * Version: 0.1.0
 * Text Domain: chassesautresor-com
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Points/PointsRepository.php';
require_once __DIR__ . '/src/Points/PointsService.php';
require_once __DIR__ . '/src/Points/PurchasePointsService.php';
require_once __DIR__ . '/src/Points/ConversionService.php';
require_once __DIR__ . '/src/Points/PointsTable.php';
require_once __DIR__ . '/src/Progress/HuntProgressRepository.php';
require_once __DIR__ . '/src/Progress/HuntProgressService.php';
require_once __DIR__ . '/src/Progress/HuntRiddleClassifier.php';
require_once __DIR__ . '/src/Progress/HuntCompletionService.php';
require_once __DIR__ . '/src/Progress/HuntWinnerRepository.php';
require_once __DIR__ . '/src/Progress/HuntWinnersTable.php';
require_once __DIR__ . '/src/Progress/HuntEngagementRepository.php';
require_once __DIR__ . '/src/Progress/HuntEngagementService.php';
require_once __DIR__ . '/src/Progress/HuntStatisticsRepository.php';
require_once __DIR__ . '/src/Progress/HuntStatisticsService.php';
require_once __DIR__ . '/src/Progress/RiddleStatisticsRepository.php';
require_once __DIR__ . '/src/Progress/RiddleStatisticsService.php';
require_once __DIR__ . '/src/Progress/UserAttemptStatisticsRepository.php';
require_once __DIR__ . '/src/Progress/UserAttemptStatisticsService.php';
require_once __DIR__ . '/src/Relationships/OrganizerRepository.php';
require_once __DIR__ . '/src/Relationships/OrganizerService.php';
require_once __DIR__ . '/src/Media/RiddleImageRepository.php';
require_once __DIR__ . '/src/Media/RiddleImageService.php';
require_once __DIR__ . '/src/Messages/UserMessageRepository.php';
require_once __DIR__ . '/src/Messages/SiteMessageService.php';
require_once __DIR__ . '/src/Messages/AccountMessageService.php';
require_once __DIR__ . '/src/Messages/UserMessagesTable.php';
require_once __DIR__ . '/src/Messages/UserMessagesCleanup.php';

if (!class_exists('PointsRepository', false)) {
    class_alias(ChassesAuTresor\Core\Points\PointsRepository::class, 'PointsRepository');
}

if (!class_exists('UserMessageRepository', false)) {
    class_alias(ChassesAuTresor\Core\Messages\UserMessageRepository::class, 'UserMessageRepository');
}

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Messages\UserMessagesTable::class, 'install']
);

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Points\PointsTable::class, 'install']
);

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Progress\HuntWinnersTable::class, 'install']
);

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Messages\UserMessagesCleanup::class, 'schedule']
);

register_deactivation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Messages\UserMessagesCleanup::class, 'unschedule']
);

add_action(
    'plugins_loaded',
    [ChassesAuTresor\Core\Messages\UserMessagesTable::class, 'maybeUpgrade']
);

add_action(
    'plugins_loaded',
    [ChassesAuTresor\Core\Points\PointsTable::class, 'maybeUpgrade']
);

add_action(
    'plugins_loaded',
    [ChassesAuTresor\Core\Progress\HuntWinnersTable::class, 'maybeUpgrade']
);

add_action(
    'plugins_loaded',
    [ChassesAuTresor\Core\Messages\UserMessagesCleanup::class, 'schedule']
);

add_action(
    ChassesAuTresor\Core\Messages\UserMessagesCleanup::HOOK,
    [ChassesAuTresor\Core\Messages\UserMessagesCleanup::class, 'run']
);

if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/src/Cli/CatCliCommand.php';

    if (!class_exists('Cat_CLI_Command', false)) {
        class_alias(ChassesAuTresor\Core\Cli\CatCliCommand::class, 'Cat_CLI_Command');
    }

    WP_CLI::add_command('cat', ChassesAuTresor\Core\Cli\CatCliCommand::class);
}
