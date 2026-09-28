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
require_once __DIR__ . '/src/Messages/UserMessageRepository.php';
require_once __DIR__ . '/src/Messages/SiteMessageService.php';
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
    [ChassesAuTresor\Core\Messages\UserMessagesCleanup::class, 'schedule']
);

add_action(
    ChassesAuTresor\Core\Messages\UserMessagesCleanup::HOOK,
    [ChassesAuTresor\Core\Messages\UserMessagesCleanup::class, 'run']
);
