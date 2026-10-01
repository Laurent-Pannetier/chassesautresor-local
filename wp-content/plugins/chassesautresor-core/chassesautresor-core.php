<?php
/**
 * Plugin Name: Chasses au Tresor Core
 * Description: Fonctionnalites metier de chassesautresor.com.
 * Version: 0.1.0
 * Text Domain: chassesautresor-com
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Support/CoreServiceFactory.php';
require_once __DIR__ . '/src/Support/table-functions.php';
require_once __DIR__ . '/src/Support/pager-functions.php';
require_once __DIR__ . '/src/Security/site-password.php';
require_once __DIR__ . '/src/Email/template.php';
require_once __DIR__ . '/src/Email/user-registration.php';
require_once __DIR__ . '/src/Email/forgot-password.php';
require_once __DIR__ . '/src/Email/woocommerce.php';
require_once __DIR__ . '/src/Points/PointsRepository.php';
require_once __DIR__ . '/src/Admin/AdminStatisticsResetService.php';
require_once __DIR__ . '/src/Admin/AdminPaymentRenderer.php';
require_once __DIR__ . '/src/Admin/admin-payment-functions.php';
require_once __DIR__ . '/src/Admin/AdminAjaxHandler.php';
require_once __DIR__ . '/src/Points/PointsService.php';
require_once __DIR__ . '/src/Points/PurchasePointsService.php';
require_once __DIR__ . '/src/Points/PurchasePointsHookHandler.php';
require_once __DIR__ . '/src/Points/ConversionService.php';
require_once __DIR__ . '/src/Points/point-service-functions.php';
require_once __DIR__ . '/src/Points/ConversionSettingsService.php';
require_once __DIR__ . '/src/Points/conversion-settings-functions.php';
require_once __DIR__ . '/src/Points/ConversionSettingsRequestHandler.php';
require_once __DIR__ . '/src/Points/ConversionRequestService.php';
require_once __DIR__ . '/src/Points/ConversionRequestHandler.php';
require_once __DIR__ . '/src/Points/ManualPointsAdjustmentService.php';
require_once __DIR__ . '/src/Points/ManualPointsAdjustmentHandler.php';
require_once __DIR__ . '/src/Points/PointsTable.php';
require_once __DIR__ . '/src/Points/HistoryPaginationRequestService.php';
require_once __DIR__ . '/src/Points/PointsHistoryRenderer.php';
require_once __DIR__ . '/src/Points/points-history-functions.php';
require_once __DIR__ . '/src/Points/PointsHistoryAjaxHandler.php';
require_once __DIR__ . '/src/Points/ConversionHistoryRenderer.php';
require_once __DIR__ . '/src/Points/ConversionHistoryAjaxHandler.php';
require_once __DIR__ . '/src/Points/ConversionModalRenderer.php';
require_once __DIR__ . '/src/Points/ConversionModalAjaxHandler.php';
require_once __DIR__ . '/src/Points/ConversionAccessService.php';
require_once __DIR__ . '/src/Progress/HuntProgressRepository.php';
require_once __DIR__ . '/src/Progress/HuntProgressService.php';
require_once __DIR__ . '/src/Progress/riddle-progress-functions.php';
require_once __DIR__ . '/src/Progress/HuntStatusService.php';
require_once __DIR__ . '/src/Progress/HuntStatusBadgeService.php';
require_once __DIR__ . '/src/Progress/hunt-status-badge-functions.php';
require_once __DIR__ . '/src/Progress/HuntNavigationAjaxHandler.php';
require_once __DIR__ . '/src/Progress/HuntNavigationAccessService.php';
require_once __DIR__ . '/src/Progress/hunt-navigation-functions.php';
require_once __DIR__ . '/src/Progress/HuntValidationAjaxHandler.php';
require_once __DIR__ . '/src/Progress/HuntStatusAjaxHandler.php';
require_once __DIR__ . '/src/Progress/HuntStatusScheduler.php';
require_once __DIR__ . '/src/Progress/HuntStatusUpdater.php';
require_once __DIR__ . '/src/Progress/hunt-status-functions.php';
require_once __DIR__ . '/src/Progress/HuntStatusSaveHookHandler.php';
require_once __DIR__ . '/src/Progress/RiddleStatusAjaxHandler.php';
require_once __DIR__ . '/src/Progress/RiddleAnswerService.php';
require_once __DIR__ . '/src/Progress/RiddleAnswerEvaluationService.php';
require_once __DIR__ . '/src/Progress/RiddleAnswerSubmissionPolicy.php';
require_once __DIR__ . '/src/Progress/RiddleAnswerSubmissionAjaxHandler.php';
require_once __DIR__ . '/src/Progress/RiddleSystemStateService.php';
require_once __DIR__ . '/src/Progress/RiddleSystemStateUpdater.php';
require_once __DIR__ . '/src/Progress/riddle-status-functions.php';
require_once __DIR__ . '/src/Progress/RiddleSystemStateSaveHookHandler.php';
require_once __DIR__ . '/src/Progress/RiddleParticipationPolicyService.php';
require_once __DIR__ . '/src/Progress/RiddleSidebarRequestPolicy.php';
require_once __DIR__ . '/src/Progress/RiddleSidebarStatisticsService.php';
require_once __DIR__ . '/src/Progress/RiddleSidebarRenderer.php';
require_once __DIR__ . '/src/Progress/RiddleSidebarAjaxHandler.php';
require_once __DIR__ . '/src/Progress/StatisticsPeriodService.php';
require_once __DIR__ . '/src/Progress/StatisticsCacheService.php';
require_once __DIR__ . '/src/Progress/StatisticsCacheInvalidationHookHandler.php';
require_once __DIR__ . '/src/Progress/HuntRiddleClassifier.php';
require_once __DIR__ . '/src/Progress/HuntCompletionService.php';
require_once __DIR__ . '/src/Progress/HuntCompletionHookHandler.php';
require_once __DIR__ . '/src/Progress/ManualAnswerNotificationService.php';
require_once __DIR__ . '/src/Progress/AnswerResultNotificationService.php';
require_once __DIR__ . '/src/Progress/ManualAttemptReviewService.php';
require_once __DIR__ . '/src/Progress/ManualAttemptReviewHandler.php';
require_once __DIR__ . '/src/Progress/HuntWinnerRepository.php';
require_once __DIR__ . '/src/Progress/HuntWinnersTable.php';
require_once __DIR__ . '/src/Progress/HuntEngagementRepository.php';
require_once __DIR__ . '/src/Progress/HuntEngagementService.php';
require_once __DIR__ . '/src/Progress/hunt-functions.php';
require_once __DIR__ . '/src/Progress/engaged-hunt-functions.php';
require_once __DIR__ . '/src/Progress/hunt-cta-functions.php';
require_once __DIR__ . '/src/Progress/HuntEngagementApplicationService.php';
require_once __DIR__ . '/src/Progress/HuntEngagementRouteHandler.php';
require_once __DIR__ . '/src/Progress/HuntStatisticsRepository.php';
require_once __DIR__ . '/src/Progress/HuntStatisticsService.php';
require_once __DIR__ . '/src/Progress/RiddleStatisticsRepository.php';
require_once __DIR__ . '/src/Progress/RiddleStatisticsService.php';
require_once __DIR__ . '/src/Progress/RiddleBarRenderer.php';
require_once __DIR__ . '/src/Progress/RiddleParticipationService.php';
require_once __DIR__ . '/src/Progress/RiddleParticipationInfoService.php';
require_once __DIR__ . '/src/Progress/RiddlePlayerPanelRenderer.php';
require_once __DIR__ . '/src/Progress/riddle-display-functions.php';
require_once __DIR__ . '/src/Progress/StatisticsParticipantRequestService.php';
require_once __DIR__ . '/src/Progress/HuntStatisticsParticipantRenderer.php';
require_once __DIR__ . '/src/Progress/RiddleStatisticsParticipantRenderer.php';
require_once __DIR__ . '/src/Progress/HuntStatisticsAjaxHandler.php';
require_once __DIR__ . '/src/Progress/HuntStatisticsApplicationService.php';
require_once __DIR__ . '/src/Progress/RiddleStatisticsAjaxHandler.php';
require_once __DIR__ . '/src/Progress/RiddleStatisticsApplicationService.php';
require_once __DIR__ . '/src/Progress/RiddleEngagementRepository.php';
require_once __DIR__ . '/src/Progress/RiddleEngagementService.php';
require_once __DIR__ . '/src/Progress/RiddleEngagementApplicationService.php';
require_once __DIR__ . '/src/Progress/HintUnlockRepository.php';
require_once __DIR__ . '/src/Progress/HintUnlockService.php';
require_once __DIR__ . '/src/Progress/HintUnlockPolicy.php';
require_once __DIR__ . '/src/Progress/HintUnlockRenderer.php';
require_once __DIR__ . '/src/Progress/HintUnlockAjaxHandler.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptRepository.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptService.php';
require_once __DIR__ . '/src/Progress/riddle-attempt-functions.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptMaintenanceService.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptAccessPolicy.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptAccessService.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptListRequestService.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptListRenderer.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptListAjaxHandler.php';
require_once __DIR__ . '/src/Progress/RiddleAttemptViewAjaxHandler.php';
require_once __DIR__ . '/src/Progress/UserAttemptStatisticsRepository.php';
require_once __DIR__ . '/src/Progress/UserAttemptStatisticsService.php';
require_once __DIR__ . '/src/Progress/UserProgressPaginationService.php';
require_once __DIR__ . '/src/Progress/UserAttemptsRenderer.php';
require_once __DIR__ . '/src/Progress/user-attempt-functions.php';
require_once __DIR__ . '/src/Progress/EngagedHuntsRecommendationService.php';
require_once __DIR__ . '/src/Progress/EngagedHuntsRenderer.php';
require_once __DIR__ . '/src/Progress/EngagedHuntsAjaxHandler.php';
require_once __DIR__ . '/src/Progress/EngagedHuntsApplicationService.php';
require_once __DIR__ . '/src/Progress/UserAttemptsAjaxHandler.php';
require_once __DIR__ . '/src/Relationships/OrganizerRepository.php';
require_once __DIR__ . '/src/Relationships/OrganizerService.php';
require_once __DIR__ . '/src/Relationships/OrganizerRequestService.php';
require_once __DIR__ . '/src/Relationships/OrganizerRequestLifecycleService.php';
require_once __DIR__ . '/src/Relationships/OrganizerConfirmationRouteHandler.php';
require_once __DIR__ . '/src/Relationships/OrganizerConfirmationEmailService.php';
require_once __DIR__ . '/src/Relationships/organizer-request-functions.php';
require_once __DIR__ . '/src/Relationships/OrganizerContactRouteHandler.php';
require_once __DIR__ . '/src/Relationships/OrganizerCtaDecisionService.php';
require_once __DIR__ . '/src/Relationships/RelationshipService.php';
require_once __DIR__ . '/src/Relationships/relationship-functions.php';
require_once __DIR__ . '/src/Relationships/HuntRiddleQueryService.php';
require_once __DIR__ . '/src/Relationships/OrganizerHuntQueryService.php';
require_once __DIR__ . '/src/Relationships/HuntRiddleCacheService.php';
require_once __DIR__ . '/src/Relationships/HuntRiddleCacheSynchronizer.php';
require_once __DIR__ . '/src/Media/RiddleImageRepository.php';
require_once __DIR__ . '/src/Media/RiddleImageService.php';
require_once __DIR__ . '/src/Media/ProtectedImagePathService.php';
require_once __DIR__ . '/src/Media/protected-image-functions.php';
require_once __DIR__ . '/src/Media/RiddleUploadDirectoryService.php';
require_once __DIR__ . '/src/Media/RiddleImageProtectionService.php';
require_once __DIR__ . '/src/Media/RiddleImageProtectionAjaxHandler.php';
require_once __DIR__ . '/src/Media/RiddleImageProtectionLifecycle.php';
require_once __DIR__ . '/src/Media/UserAvatarUploadAjaxHandler.php';
require_once __DIR__ . '/src/Media/UserAvatarHookHandler.php';
require_once __DIR__ . '/src/Media/ProtectedAssetRouteHandler.php';
require_once __DIR__ . '/src/Media/ProtectedRiddleAssetService.php';
require_once __DIR__ . '/src/Media/ProtectedSolutionAssetService.php';
require_once __DIR__ . '/src/Content/RiddleCompletionService.php';
require_once __DIR__ . '/src/Content/HuntFilterRequestService.php';
require_once __DIR__ . '/src/Content/HuntCardRenderer.php';
require_once __DIR__ . '/src/Content/HuntFilterAjaxHandler.php';
require_once __DIR__ . '/src/Content/HuntFilterApplicationService.php';
require_once __DIR__ . '/src/Content/CompletionCacheManager.php';
require_once __DIR__ . '/src/Content/completion-functions.php';
require_once __DIR__ . '/src/Content/CompletionCacheSaveHookHandler.php';
require_once __DIR__ . '/src/Content/HuntFeatureCacheManager.php';
require_once __DIR__ . '/src/Content/HuntFeatureCacheSaveHookHandler.php';
require_once __DIR__ . '/src/Content/RiddleActionPolicyService.php';
require_once __DIR__ . '/src/Content/RiddleCreationService.php';
require_once __DIR__ . '/src/Content/RiddleCreationRequestService.php';
require_once __DIR__ . '/src/Content/RiddleCreationRouteHandler.php';
require_once __DIR__ . '/src/Content/OrganizerCompletionService.php';
require_once __DIR__ . '/src/Content/OrganizerCreationService.php';
require_once __DIR__ . '/src/Content/OrganizerRelationshipSaveHookHandler.php';
require_once __DIR__ . '/src/Content/OrganizerMutationService.php';
require_once __DIR__ . '/src/Content/AcfRelationshipMutationService.php';
require_once __DIR__ . '/src/Content/OrganizerFieldMutationAjaxHandler.php';
require_once __DIR__ . '/src/Content/PublicLinkService.php';
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
require_once __DIR__ . '/src/Content/HintOrderingApplicationService.php';
require_once __DIR__ . '/src/Content/HintOrderingLifecycleHookHandler.php';
require_once __DIR__ . '/src/Content/HintCreationService.php';
require_once __DIR__ . '/src/Content/HintCreationRequestService.php';
require_once __DIR__ . '/src/Content/HintPostFactory.php';
require_once __DIR__ . '/src/Content/HintCreationRouteHandler.php';
require_once __DIR__ . '/src/Content/hint-functions.php';
require_once __DIR__ . '/src/Content/HintDeletionService.php';
require_once __DIR__ . '/src/Content/HintDeletionAjaxHandler.php';
require_once __DIR__ . '/src/Content/HintDeletionLifecycleService.php';
require_once __DIR__ . '/src/Content/HintDeletionLifecycleHookHandler.php';
require_once __DIR__ . '/src/Content/HintRouteRegistrar.php';
require_once __DIR__ . '/src/Content/HintFieldPolicyService.php';
require_once __DIR__ . '/src/Content/RelatedContentAccessResolver.php';
require_once __DIR__ . '/src/Content/HintFieldMutationService.php';
require_once __DIR__ . '/src/Content/HintFieldMutationAjaxHandler.php';
require_once __DIR__ . '/src/Content/HintMutationService.php';
require_once __DIR__ . '/src/Content/HintModalAjaxHandler.php';
require_once __DIR__ . '/src/Content/HintManagementService.php';
require_once __DIR__ . '/src/Content/HintRiddleOptionsAjaxHandler.php';
require_once __DIR__ . '/src/Content/HintCardAjaxHandler.php';
require_once __DIR__ . '/src/Content/HintTableAjaxHandler.php';
require_once __DIR__ . '/src/Content/HintRelationshipService.php';
require_once __DIR__ . '/src/Content/HintRelationshipSaveHookHandler.php';
require_once __DIR__ . '/src/Content/HintRelationshipFieldHookHandler.php';
require_once __DIR__ . '/src/Content/HintCacheSaveHookHandler.php';
require_once __DIR__ . '/src/Content/HintRedirectHandler.php';
require_once __DIR__ . '/src/Content/HuntPublicationStatusService.php';
require_once __DIR__ . '/src/Content/HuntValidationService.php';
require_once __DIR__ . '/src/Content/HuntValidationAccessResolver.php';
require_once __DIR__ . '/src/Messages/HuntCorrectionMessageService.php';
require_once __DIR__ . '/src/Messages/HuntValidationMessageHookHandler.php';
require_once __DIR__ . '/src/Content/hunt-validation-functions.php';
require_once __DIR__ . '/src/Content/HuntModerationService.php';
require_once __DIR__ . '/src/Content/HuntModerationMutationService.php';
require_once __DIR__ . '/src/Content/HuntModerationRequestHandler.php';
require_once __DIR__ . '/src/Content/HuntModerationOrganizerService.php';
require_once __DIR__ . '/src/Content/HuntModerationNotificationService.php';
require_once __DIR__ . '/src/Content/HuntDateMutationService.php';
require_once __DIR__ . '/src/Content/HuntDateMutationAjaxHandler.php';
require_once __DIR__ . '/src/Content/HuntDateValidationHookHandler.php';
require_once __DIR__ . '/src/Content/HuntLinkMutationService.php';
require_once __DIR__ . '/src/Content/HuntRewardMutationService.php';
require_once __DIR__ . '/src/Content/HuntFieldMutationService.php';
require_once __DIR__ . '/src/Content/HuntFieldMutationAjaxHandler.php';
require_once __DIR__ . '/src/Content/HuntMutationLifecycleHookHandler.php';
require_once __DIR__ . '/src/Content/HuntClosureService.php';
require_once __DIR__ . '/src/Content/HuntValidationRequestRouteHandler.php';
require_once __DIR__ . '/src/Content/HuntWelcomeModalViewHookHandler.php';
require_once __DIR__ . '/src/Content/HuntViewMaintenanceHookHandler.php';
require_once __DIR__ . '/src/Content/HuntDisplayCacheInvalidationHookHandler.php';
require_once __DIR__ . '/src/Content/HuntDisplayViewCacheService.php';
require_once __DIR__ . '/src/Content/HuntCreationRequestService.php';
require_once __DIR__ . '/src/Content/HuntPostFactory.php';
require_once __DIR__ . '/src/Content/HuntCreationRouteHandler.php';
require_once __DIR__ . '/src/Content/HuntDeletionService.php';
require_once __DIR__ . '/src/Content/HuntDeletionAjaxHandler.php';
require_once __DIR__ . '/src/Content/HuntInitializationService.php';
require_once __DIR__ . '/src/Content/HuntInitializationHookHandler.php';
require_once __DIR__ . '/src/Content/SolutionAvailabilityService.php';
require_once __DIR__ . '/src/Content/SolutionCacheService.php';
require_once __DIR__ . '/src/Content/SolutionCacheUpdater.php';
require_once __DIR__ . '/src/Content/solution-functions.php';
require_once __DIR__ . '/src/Content/SolutionCreationService.php';
require_once __DIR__ . '/src/Content/SolutionCreationRouteHandler.php';
require_once __DIR__ . '/src/Content/SolutionDeletionService.php';
require_once __DIR__ . '/src/Content/SolutionDeletionAjaxHandler.php';
require_once __DIR__ . '/src/Content/SolutionDisplayService.php';
require_once __DIR__ . '/src/Content/SolutionFieldPolicyService.php';
require_once __DIR__ . '/src/Content/SolutionFileInputService.php';
require_once __DIR__ . '/src/Content/SolutionManagementService.php';
require_once __DIR__ . '/src/Content/SolutionManagementAjaxHandler.php';
require_once __DIR__ . '/src/Content/SolutionModalPolicyService.php';
require_once __DIR__ . '/src/Content/SolutionModalAjaxHandler.php';
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
require_once __DIR__ . '/src/Content/access-functions.php';
require_once __DIR__ . '/src/Content/RiddleFieldPolicyService.php';
require_once __DIR__ . '/src/Content/RiddleManagementService.php';
require_once __DIR__ . '/src/Content/RiddleMutationService.php';
require_once __DIR__ . '/src/Content/RiddleFieldMutationAjaxHandler.php';
require_once __DIR__ . '/src/Content/RiddleMutationLifecycleHookHandler.php';
require_once __DIR__ . '/src/Content/RiddleOrderingService.php';
require_once __DIR__ . '/src/Content/RiddleOrderingAjaxHandler.php';
require_once __DIR__ . '/src/Content/RiddlePostFactory.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipService.php';
require_once __DIR__ . '/src/Content/RiddleCacheMutationService.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipHookHandler.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipFilterHandler.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipCleanupService.php';
require_once __DIR__ . '/src/Content/RiddleRelationshipLifecycleService.php';
require_once __DIR__ . '/src/Content/RiddleRouteRegistrar.php';
require_once __DIR__ . '/src/Content/HuntManagementService.php';
require_once __DIR__ . '/src/Content/ContentPanelAccessService.php';
require_once __DIR__ . '/src/Content/ContentFieldAccessService.php';
require_once __DIR__ . '/src/Content/WordPressContentAccessResolver.php';
require_once __DIR__ . '/src/Content/ContentFieldPolicyService.php';
require_once __DIR__ . '/src/Content/ContentModificationService.php';
require_once __DIR__ . '/src/Content/content-access.php';
require_once __DIR__ . '/src/Content/RelatedContentActionService.php';
require_once __DIR__ . '/src/Content/ContentCreationService.php';
require_once __DIR__ . '/src/Content/HuntAccessService.php';
require_once __DIR__ . '/src/Content/RiddleDeletionService.php';
require_once __DIR__ . '/src/Content/RiddleDeletionAjaxHandler.php';
require_once __DIR__ . '/src/Content/RiddlePrerequisiteService.php';
require_once __DIR__ . '/src/Content/RiddlePrerequisiteAjaxHandler.php';
require_once __DIR__ . '/src/Content/RiddleSolutionAttachmentService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFilePolicyService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFilePublicationService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFileScheduler.php';
require_once __DIR__ . '/src/Content/RiddleSolutionFileStorageService.php';
require_once __DIR__ . '/src/Content/RiddleSolutionUploadService.php';
require_once __DIR__ . '/src/Content/ContentQueryAccessService.php';
require_once __DIR__ . '/src/Content/OrganizerRoleService.php';
require_once __DIR__ . '/src/Content/OrganizerRoleAssignmentHookHandler.php';
require_once __DIR__ . '/src/Content/WordPressAccessPolicyHookHandler.php';
require_once __DIR__ . '/src/Content/BackOfficeAccessHookHandler.php';
require_once __DIR__ . '/src/Content/RiddleRenderCacheHookHandler.php';
require_once __DIR__ . '/src/Content/ContentScreenAccessHookHandler.php';
require_once __DIR__ . '/src/Content/RiddleAccessRedirectHandler.php';
require_once __DIR__ . '/src/Content/HuntOrganizerAssignmentHookHandler.php';
require_once __DIR__ . '/src/Content/OrganizerNavigationService.php';
require_once __DIR__ . '/src/Messages/UserMessageRepository.php';
require_once __DIR__ . '/src/Messages/SiteMessageService.php';
require_once __DIR__ . '/src/Messages/AccountMessageService.php';
require_once __DIR__ . '/src/Messages/account-message-functions.php';
require_once __DIR__ . '/src/Messages/important-messages.php';
require_once __DIR__ . '/src/Messages/AccountMessageDismissalAjaxHandler.php';
require_once __DIR__ . '/src/Messages/AccountSectionAccessService.php';
require_once __DIR__ . '/src/Messages/AccountStatisticsRenderer.php';
require_once __DIR__ . '/src/Messages/AccountToolsRenderer.php';
require_once __DIR__ . '/src/Messages/organizer-moderation-functions.php';
require_once __DIR__ . '/src/Messages/AccountOrganizersRenderer.php';
require_once __DIR__ . '/src/Messages/AccountSectionRenderer.php';
require_once __DIR__ . '/src/Messages/AccountSectionAjaxHandler.php';
require_once __DIR__ . '/src/Messages/UserMessagesTable.php';
require_once __DIR__ . '/src/Messages/UserMessagesCleanup.php';
require_once __DIR__ . '/src/Messages/LegacySiteMessageCleanup.php';
require_once __DIR__ . '/src/Messages/AccountLegacyRouteHandler.php';

if (!class_exists('PointsRepository', false)) {
    class_alias(ChassesAuTresor\Core\Points\PointsRepository::class, 'PointsRepository');
}

ChassesAuTresor\Core\Admin\AdminAjaxHandler::register('add_action');

if (!class_exists('UserMessageRepository', false)) {
    class_alias(ChassesAuTresor\Core\Messages\UserMessageRepository::class, 'UserMessageRepository');
}

ChassesAuTresor\Core\Content\RiddleRelationshipHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntFilterAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddleRelationshipFilterHandler::register('add_filter');
ChassesAuTresor\Core\Content\RiddleOrderingAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddleDeletionAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntInitializationHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntDeletionAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntCreationRouteHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddleCreationRouteHandler::register('add_action');
ChassesAuTresor\Core\Content\HintCreationRouteHandler::register('add_action');
ChassesAuTresor\Core\Content\SolutionCreationRouteHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntDateMutationAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntDateValidationHookHandler::register('add_filter');
ChassesAuTresor\Core\Content\HuntFieldMutationAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddleFieldMutationAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\OrganizerFieldMutationAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\OrganizerRelationshipSaveHookHandler::register('add_action');
ChassesAuTresor\Core\Content\OrganizerRoleAssignmentHookHandler::register('add_action');
ChassesAuTresor\Core\Content\WordPressAccessPolicyHookHandler::register('add_action', 'add_filter');
ChassesAuTresor\Core\Content\BackOfficeAccessHookHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddleRenderCacheHookHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddleMutationLifecycleHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntMutationLifecycleHookHandler::register('add_action');
ChassesAuTresor\Core\Content\ContentScreenAccessHookHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddleAccessRedirectHandler::register('add_action');
ChassesAuTresor\Core\Content\HintRedirectHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntOrganizerAssignmentHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntWelcomeModalViewHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntViewMaintenanceHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntDisplayCacheInvalidationHookHandler::register('add_action', 'add_filter');
ChassesAuTresor\Core\Content\HuntModerationRequestHandler::register('add_action');
ChassesAuTresor\Core\Relationships\OrganizerConfirmationRouteHandler::register('add_action');
ChassesAuTresor\Core\Relationships\OrganizerContactRouteHandler::register('add_action', 'add_filter');
ChassesAuTresor\Core\Points\PurchasePointsHookHandler::register('add_action');
ChassesAuTresor\Core\Points\ConversionSettingsRequestHandler::register('add_action');
ChassesAuTresor\Core\Points\ConversionRequestHandler::register('add_action');
ChassesAuTresor\Core\Points\ManualPointsAdjustmentHandler::register('add_action');
ChassesAuTresor\Core\Content\CompletionCacheSaveHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HuntFeatureCacheSaveHookHandler::register('add_action');
ChassesAuTresor\Core\Progress\HuntStatusAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\HuntNavigationAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\HuntValidationAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\HuntStatusScheduler::register('add_action');
ChassesAuTresor\Core\Progress\HuntStatusSaveHookHandler::register('add_action');
ChassesAuTresor\Core\Progress\RiddleStatusAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\RiddleAnswerSubmissionAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\HuntCompletionHookHandler::register('add_action');
ChassesAuTresor\Core\Progress\RiddleAttemptViewAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\RiddleAttemptListAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\RiddleSystemStateSaveHookHandler::register('add_action');
ChassesAuTresor\Core\Progress\StatisticsCacheInvalidationHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HintFieldMutationAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HintModalAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HintDeletionAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HintRiddleOptionsAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HintCardAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HintTableAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\HintOrderingLifecycleHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HintDeletionLifecycleHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HintRelationshipSaveHookHandler::register('add_action');
ChassesAuTresor\Core\Content\HintRelationshipFieldHookHandler::register('add_filter');
ChassesAuTresor\Core\Content\HintCacheSaveHookHandler::register('add_action');
ChassesAuTresor\Core\Content\SolutionDeletionAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\SolutionModalAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\SolutionManagementAjaxHandler::register('add_action');
ChassesAuTresor\Core\Media\RiddleImageProtectionAjaxHandler::register('add_action');
ChassesAuTresor\Core\Media\RiddleImageProtectionLifecycle::register('add_action', 'add_filter');
ChassesAuTresor\Core\Messages\AccountMessageDismissalAjaxHandler::register('add_action');
ChassesAuTresor\Core\Messages\HuntValidationMessageHookHandler::register('add_action');
ChassesAuTresor\Core\Messages\AccountSectionAjaxHandler::register('add_action');
ChassesAuTresor\Core\Messages\AccountLegacyRouteHandler::register('add_action', 'add_filter');
ChassesAuTresor\Core\Points\PointsHistoryAjaxHandler::register('add_action');
ChassesAuTresor\Core\Points\ConversionHistoryAjaxHandler::register('add_action');
ChassesAuTresor\Core\Points\ConversionModalAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\EngagedHuntsAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\UserAttemptsAjaxHandler::register('add_action');
ChassesAuTresor\Core\Content\RiddlePrerequisiteAjaxHandler::register('add_action');
ChassesAuTresor\Core\Media\UserAvatarUploadAjaxHandler::register('add_action');
ChassesAuTresor\Core\Media\UserAvatarHookHandler::register('add_filter');
ChassesAuTresor\Core\Media\ProtectedAssetRouteHandler::register('add_action', 'add_filter');
ChassesAuTresor\Core\Progress\HuntStatisticsAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\RiddleStatisticsAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\HintUnlockAjaxHandler::register('add_action');
ChassesAuTresor\Core\Progress\RiddleSidebarAjaxHandler::register('add_action');

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
    [ChassesAuTresor\Core\Progress\HuntStatusScheduler::class, 'schedule']
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
    [ChassesAuTresor\Core\Progress\HuntStatusScheduler::class, 'unschedule']
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
    'init',
    [ChassesAuTresor\Core\Messages\LegacySiteMessageCleanup::class, 'run']
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
