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
require_once __DIR__ . '/src/Progress/HuntStatusService.php';
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
require_once __DIR__ . '/src/Progress/RiddleEngagementRepository.php';
require_once __DIR__ . '/src/Progress/RiddleEngagementService.php';
require_once __DIR__ . '/src/Progress/HintUnlockRepository.php';
require_once __DIR__ . '/src/Progress/HintUnlockService.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptRepository.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptService.php';
require_once __DIR__ . '/src/Progress/UserAttemptStatisticsRepository.php';
require_once __DIR__ . '/src/Progress/UserAttemptStatisticsService.php';
require_once __DIR__ . '/src/Relationships/OrganizerRepository.php';
require_once __DIR__ . '/src/Relationships/OrganizerService.php';
require_once __DIR__ . '/src/Relationships/OrganizerRequestService.php';
require_once __DIR__ . '/src/Relationships/RelationshipService.php';
require_once __DIR__ . '/src/Relationships/HuntRiddleQueryService.php';
require_once __DIR__ . '/src/Relationships/OrganizerHuntQueryService.php';
require_once __DIR__ . '/src/Relationships/HuntRiddleCacheService.php';
require_once __DIR__ . '/src/Media/RiddleImageRepository.php';
require_once __DIR__ . '/src/Media/RiddleImageService.php';
require_once __DIR__ . '/src/Media/RiddleUploadDirectoryService.php';
require_once __DIR__ . '/src/Content/RiddleCompletionService.php';
require_once __DIR__ . '/src/Content/RiddleActionPolicyService.php';
require_once __DIR__ . '/src/Content/RiddleCreationService.php';
require_once __DIR__ . '/src/Content/RiddleCreationRequestService.php';
require_once __DIR__ . '/src/Content/OrganizerCompletionService.php';
require_once __DIR__ . '/src/Content/OrganizerCreationService.php';
require_once __DIR__ . '/src/Content/OrganizerMutationService.php';
require_once __DIR__ . '/src/Content/HuntCompletionService.php';
require_once __DIR__ . '/src/Content/HuntFeatureService.php';
require_once __DIR__ . '/src/Content/HintQueryService.php';
require_once __DIR__ . '/src/Content/HintStatusService.php';
require_once __DIR__ . '/src/Content/HintCacheService.php';
require_once __DIR__ . '/src/Content/HintCacheUpdater.php';
require_once __DIR__ . '/src/Content/HintScheduler.php';
require_once __DIR__ . '/src/Content/HintTitleService.php';
require_once __DIR__ . '/src/Content/HintOrderingService.php';
require_once __DIR__ . '/src/Content/HintOrderingUpdater.php';
require_once __DIR__ . '/src/Content/HintCreationService.php';
require_once __DIR__ . '/src/Content/HintCreationRequestService.php';
require_once __DIR__ . '/src/Content/HintPostFactory.php';
require_once __DIR__ . '/src/Content/HintDeletionService.php';
require_once __DIR__ . '/src/Content/HintDeletionLifecycleService.php';
require_once __DIR__ . '/src/Content/HintRouteRegistrar.php';
require_once __DIR__ . '/src/Content/HintFieldPolicyService.php';
require_once __DIR__ . '/src/Content/HintFieldMutationService.php';
require_once __DIR__ . '/src/Content/HintMutationService.php';
require_once __DIR__ . '/src/Content/HintManagementService.php';
require_once __DIR__ . '/src/Content/HintRelationshipService.php';
require_once __DIR__ . '/src/Content/HintRedirectHandler.php';
require_once __DIR__ . '/src/Content/HuntPublicationStatusService.php';
require_once __DIR__ . '/src/Content/HuntValidationService.php';
require_once __DIR__ . '/src/Content/HuntDateMutationService.php';
require_once __DIR__ . '/src/Content/HuntLinkMutationService.php';
require_once __DIR__ . '/src/Content/HuntRewardMutationService.php';
require_once __DIR__ . '/src/Content/HuntFieldMutationService.php';
require_once __DIR__ . '/src/Content/HuntClosureService.php';
require_once __DIR__ . '/src/Content/HuntCreationRequestService.php';
require_once __DIR__ . '/src/Content/HuntPostFactory.php';
require_once __DIR__ . '/src/Content/HuntDeletionService.php';
require_once __DIR__ . '/src/Content/HuntInitializationService.php';
require_once __DIR__ . '/src/Content/SolutionAvailabilityService.php';
require_once __DIR__ . '/src/Content/SolutionCacheService.php';
require_once __DIR__ . '/src/Content/SolutionCacheUpdater.php';
require_once __DIR__ . '/src/Content/SolutionCreationService.php';
require_once __DIR__ . '/src/Content/SolutionDeletionService.php';
require_once __DIR__ . '/src/Content/SolutionDisplayService.php';
require_once __DIR__ . '/src/Content/SolutionFieldPolicyService.php';
require_once __DIR__ . '/src/Content/SolutionFileInputService.php';
require_once __DIR__ . '/src/Content/SolutionManagementService.php';
require_once __DIR__ . '/src/Content/SolutionModalPolicyService.php';
require_once __DIR__ . '/src/Content/SolutionMutationService.php';
require_once __DIR__ . '/src/Content/SolutionPostFactory.php';
require_once __DIR__ . '/src/Content/SolutionPublicationService.php';
require_once __DIR__ . '/src/Content/SolutionPublicationPlanner.php';
require_once __DIR__ . '/src/Content/SolutionQueryService.php';
require_once __DIR__ . '/src/Content/SolutionRedirectHandler.php';
require_once __DIR__ . '/src/Content/SolutionRouteRegistrar.php';
require_once __DIR__ . '/src/Content/SolutionScheduler.php';
require_once __DIR__ . '/src/Content/SolutionSaveHandler.php';
require_once __DIR__ . '/src/Content/SolutionAccessService.php';
require_once __DIR__ . '/src/Content/RiddleAccessService.php';
require_once __DIR__ . '/src/Content/RiddleFieldPolicyService.php';
require_once __DIR__ . '/src/Content/RiddleManagementService.php';
require_once __DIR__ . '/src/Content/RiddleMutationService.php';
require_once __DIR__ . '/src/Content/RiddleOrderingService.php';
require_once __DIR__ . '/src/Content/RiddlePostFactory.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipService.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipCleanupService.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipLifecycleService.php';
require_once __DIR__ . '/src/Content/RiddleRouteRegistrar.php';
require_once __DIR__ . '/src/Content/HuntManagementService.php';
require_once __DIR__ . '/src/Content/ContentPanelAccessService.php';
require_once __DIR__ . '/src/Content/ContentFieldAccessService.php';
require_once __DIR__ . '/src/Content/ContentFieldPolicyService.php';
require_once __DIR__ . '/src/Content/ContentModificationService.php';
require_once __DIR__ . '/src/Content/RelatedContentActionService.php';
require_once __DIR__ . '/src/Content/ContentCreationService.php';
require_once __DIR__ . '/src/Content/HuntAccessService.php';
require_once __DIR__ . '/src/Content/RiddleDeletionService.php';
require_once __DIR__ . '/src/Content/RiddlePrerequisiteService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionAttachmentService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFilePolicyService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFilePublicationService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFileScheduler.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFileStorageService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionUploadService.php';
require_once __DIR__ . '/src/Content/ContentQueryAccessService.php';
require_once __DIR__ . '/src/Content/OrganizerRoleService.php';
require_once __DIR__ . '/src/Content/OrganizerNavigationService.php';
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
    [ChassesAuTresor\Core\Content\HintScheduler::class, 'schedule']
);

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Content\HintRouteRegistrar::class, 'flush']
);

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Content\SolutionRouteRegistrar::class, 'flush']
);

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Content\RiddleRouteRegistrar::class, 'flush']
);

register_activation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Content\SolutionScheduler::class, 'schedule']
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

register_deactivation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Content\HintScheduler::class, 'unschedule']
);

register_deactivation_hook(
    __FILE__,
    [ChassesAuTresor\Core\Content\SolutionScheduler::class, 'unschedule']
);

add_action(
    'plugins_loaded',
    [ChassesAuTresor\Core\Messages\UserMessagesTable::class, 'maybeUpgrade']
);

add_action(
    'plugins_loaded',
    [ChassesAuTresor\Core\Content\HintScheduler::class, 'schedule']
);

add_action(
    ChassesAuTresor\Core\Content\HintScheduler::HOOK,
    [ChassesAuTresor\Core\Content\HintScheduler::class, 'run']
);

add_action(
    'plugins_loaded',
    [ChassesAuTresor\Core\Content\SolutionScheduler::class, 'schedule']
);

add_action(
    ChassesAuTresor\Core\Content\SolutionScheduler::HOOK,
    [ChassesAuTresor\Core\Content\SolutionScheduler::class, 'run']
);

add_action(
    ChassesAuTresor\Core\Content\SolutionScheduler::PROCESS_HOOK,
    [ChassesAuTresor\Core\Content\SolutionPublicationService::class, 'makeAccessible']
);

add_action(
    'publier_solution_programmee',
    [ChassesAuTresor\Core\Content\SolutionPublicationService::class, 'makeAccessible']
);

add_action(
    ChassesAuTresor\Core\Content\RiddleSolutionFileScheduler::HOOK,
    [ChassesAuTresor\Core\Content\RiddleSolutionFilePublicationService::class, 'publish']
);

add_action(
    'acf/save_post',
    [ChassesAuTresor\Core\Content\SolutionSaveHandler::class, 'handle'],
    40
);

add_action(
    'template_redirect',
    [ChassesAuTresor\Core\Content\SolutionRedirectHandler::class, 'redirectIfViewingSolution']
);

add_action(
    'init',
    [ChassesAuTresor\Core\Content\HintRouteRegistrar::class, 'register']
);

add_action(
    'init',
    [ChassesAuTresor\Core\Content\HintRouteRegistrar::class, 'maybeFlush'],
    20
);

add_action(
    'init',
    [ChassesAuTresor\Core\Content\SolutionRouteRegistrar::class, 'register']
);

add_action(
    'init',
    [ChassesAuTresor\Core\Content\SolutionRouteRegistrar::class, 'maybeFlush'],
    20
);

add_action(
    'init',
    [ChassesAuTresor\Core\Content\RiddleRouteRegistrar::class, 'register']
);

add_action(
    'init',
    [ChassesAuTresor\Core\Content\RiddleRouteRegistrar::class, 'maybeFlush'],
    20
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
