<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntOrganizerAssignmentHookHandler;
use ChassesAuTresor\Core\Content\OrganizerRoleAssignmentHookHandler;
use ChassesAuTresor\Core\Content\WordPressAccessPolicyHookHandler;
use ChassesAuTresor\Core\Content\BackOfficeAccessHookHandler;
use ChassesAuTresor\Core\Content\RiddleRenderCacheHookHandler;
use ChassesAuTresor\Core\Content\ContentScreenAccessHookHandler;
use ChassesAuTresor\Core\Content\HuntWelcomeModalViewHookHandler;
use ChassesAuTresor\Core\Content\HuntViewMaintenanceHookHandler;
use ChassesAuTresor\Core\Content\HuntDisplayCacheInvalidationHookHandler;
use ChassesAuTresor\Core\Content\HuntModerationRequestHandler;
use ChassesAuTresor\Core\Points\ConversionSettingsRequestHandler;
use ChassesAuTresor\Core\Points\ConversionRequestHandler;
use ChassesAuTresor\Core\Points\ManualPointsAdjustmentHandler;
use ChassesAuTresor\Core\Points\PurchasePointsHookHandler;
use ChassesAuTresor\Core\Progress\HuntCompletionHookHandler;
use ChassesAuTresor\Core\Relationships\OrganizerConfirmationRouteHandler;
use ChassesAuTresor\Core\Relationships\OrganizerContactRouteHandler;
use ChassesAuTresor\Core\Media\ProtectedAssetRouteHandler;
use ChassesAuTresor\Core\Messages\AccountLegacyRouteHandler;
use ChassesAuTresor\Core\Content\RiddleAccessRedirectHandler;
use PHPUnit\Framework\TestCase;

final class BusinessHookOwnershipTest extends TestCase
{
    public function testOrganizerContactRouteIsRegisteredByCore(): void {
        $actions = [];
        $filters = [];

        OrganizerContactRouteHandler::register(
            static function (...$arguments) use (&$actions): void {
                $actions[] = $arguments;
            },
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame([['init', [OrganizerContactRouteHandler::class, 'registerEndpoint']]], $actions);
        self::assertSame([['query_vars', [OrganizerContactRouteHandler::class, 'addQueryVariable']]], $filters);
    }

    public function testRiddleAccessRedirectIsRegisteredByCore(): void {
        $hooks = [];

        RiddleAccessRedirectHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [['template_redirect', [RiddleAccessRedirectHandler::class, 'handle']]],
            $hooks
        );
    }

    public function testLegacyAccountRoutesAreRegisteredByCore(): void {
        $actions = [];
        $filters = [];

        AccountLegacyRouteHandler::register(
            static function (...$arguments) use (&$actions): void {
                $actions[] = $arguments;
            },
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame([['init', [AccountLegacyRouteHandler::class, 'registerRoutes']]], $actions);
        self::assertSame(
            [
                ['query_vars', [AccountLegacyRouteHandler::class, 'addQueryVariables']],
                ['template_include', [AccountLegacyRouteHandler::class, 'redirectLegacyRoute']],
            ],
            $filters
        );
    }

    public function testProtectedAssetRoutesAreRegisteredByCore(): void {
        $actions = [];
        $filters = [];

        ProtectedAssetRouteHandler::register(
            static function (...$arguments) use (&$actions): void {
                $actions[] = $arguments;
            },
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['init', [ProtectedAssetRouteHandler::class, 'registerRoutes'], 1],
                ['template_redirect', [ProtectedAssetRouteHandler::class, 'dispatch']],
            ],
            $actions
        );
        self::assertSame(
            [['query_vars', [ProtectedAssetRouteHandler::class, 'addQueryVariables']]],
            $filters
        );
    }

    public function testContentScreenAccessHooksAreRegisteredByCore(): void {
        $hooks = [];

        ContentScreenAccessHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['load-post-new.php', [ContentScreenAccessHookHandler::class, 'handleCreateScreen']],
                ['load-post.php', [ContentScreenAccessHookHandler::class, 'handleEditScreen']],
            ],
            $hooks
        );
    }

    public function testRiddleRenderCacheHooksAreRegisteredByCore(): void {
        $hooks = [];

        RiddleRenderCacheHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertCount(10, $hooks);
        self::assertSame(['save_post_enigme', [RiddleRenderCacheHookHandler::class, 'clear'], 10, 1], $hooks[0]);
        self::assertSame(
            ['save_post_solution', [RiddleRenderCacheHookHandler::class, 'clearAfterSolutionSave'], 20, 1],
            $hooks[1]
        );
        self::assertSame(
            ['enigme_resolue', [RiddleRenderCacheHookHandler::class, 'clearAfterSolve'], 10, 2],
            $hooks[2]
        );
        self::assertSame(
            ['updated_user_meta', [RiddleRenderCacheHookHandler::class, 'bumpPermissionsVersion'], 10, 4],
            $hooks[8]
        );
    }

    public function testBackOfficeAccessPolicyIsRegisteredByCore(): void {
        $hooks = [];

        BackOfficeAccessHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame([['admin_init', [BackOfficeAccessHookHandler::class, 'handle']]], $hooks);
    }

    public function testWordPressAccessPoliciesAreRegisteredByCore(): void {
        $actions = [];
        $filters = [];

        WordPressAccessPolicyHookHandler::register(
            static function (...$arguments) use (&$actions): void {
                $actions[] = $arguments;
            },
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame(
            [['pre_get_posts', [WordPressAccessPolicyHookHandler::class, 'extendVisiblePostStatuses']]],
            $actions
        );
        self::assertSame(
            [
                ['ajax_query_attachments_args', [WordPressAccessPolicyHookHandler::class, 'restrictMediaToAuthor']],
                ['rest_attachment_query', [WordPressAccessPolicyHookHandler::class, 'restrictMediaToAuthor']],
                ['ajax_query_attachments_args', [WordPressAccessPolicyHookHandler::class, 'filterRiddleMedia'], 15],
                ['use_block_editor_for_post', [WordPressAccessPolicyHookHandler::class, 'filterBlockEditor'], 10, 2],
                ['user_has_cap', [WordPressAccessPolicyHookHandler::class, 'filterCapabilities'], 10, 4],
                [
                    'acf/load_field/name=utilisateurs_associes',
                    [WordPressAccessPolicyHookHandler::class, 'restrictOrganizerUsersField'],
                ],
            ],
            $filters
        );
    }

    public function testOrganizerRoleAssignmentHookIsRegisteredByCore(): void {
        $hooks = [];

        OrganizerRoleAssignmentHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['save_post', [OrganizerRoleAssignmentHookHandler::class, 'handle'], 10, 3],
            $hooks[0]
        );
    }

    public function testPurchasePointsHookIsRegisteredByCore(): void
    {
        $hooks = [];

        PurchasePointsHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['woocommerce_thankyou', [PurchasePointsHookHandler::class, 'handle']],
            $hooks[0]
        );
    }

    public function testHuntOrganizerAssignmentHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntOrganizerAssignmentHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['save_post', [HuntOrganizerAssignmentHookHandler::class, 'handle'], 10, 2],
            $hooks[0]
        );
    }

    public function testHuntWelcomeModalViewHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntWelcomeModalViewHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['wp_footer', [HuntWelcomeModalViewHookHandler::class, 'handle'], 99],
            $hooks[0]
        );
    }

    public function testHuntViewMaintenanceHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntViewMaintenanceHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['template_redirect', [HuntViewMaintenanceHookHandler::class, 'handle']],
            $hooks[0]
        );
    }

    public function testHuntDisplayCacheInvalidationHooksAreRegisteredByCore(): void
    {
        $actions = [];
        $filters = [];

        HuntDisplayCacheInvalidationHookHandler::register(
            static function (...$arguments) use (&$actions): void {
                $actions[] = $arguments;
            },
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame('acf/save_post', $actions[0][0]);
        self::assertSame('save_post', $actions[1][0]);
        self::assertSame('chasse_engagement_created', $actions[2][0]);
        self::assertSame('set_object_terms', $actions[3][0]);
        self::assertSame('chassesautresor_hunt_display_cache_clear_requested', $actions[4][0]);
        self::assertSame('acf/update_value/name=utilisateurs_associes', $filters[0][0]);
    }

    public function testConversionSettingsHooksAreRegisteredByCore(): void
    {
        $hooks = [];

        ConversionSettingsRequestHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['init', [ConversionSettingsRequestHandler::class, 'initialize']],
                ['init', [ConversionSettingsRequestHandler::class, 'handleUpdate']],
            ],
            $hooks
        );
    }

    public function testConversionRequestHookIsRegisteredByCore(): void
    {
        $hooks = [];

        ConversionRequestHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame([['init', [ConversionRequestHandler::class, 'handle']]], $hooks);
    }

    public function testManualPointsAdjustmentHookIsRegisteredByCore(): void
    {
        $hooks = [];

        ManualPointsAdjustmentHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame([['init', [ManualPointsAdjustmentHandler::class, 'handle']]], $hooks);
    }

    public function testHuntModerationRequestHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntModerationRequestHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['admin_post_traiter_validation_chasse', [HuntModerationRequestHandler::class, 'handle']],
            $hooks[0]
        );
    }

    public function testHuntCompletionHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntCompletionHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['enigme_resolue', [HuntCompletionHookHandler::class, 'handle'], 10, 2],
            $hooks[0]
        );
    }

    public function testOrganizerConfirmationRoutesAreRegisteredByCore(): void
    {
        $hooks = [];

        OrganizerConfirmationRouteHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['init', [OrganizerConfirmationRouteHandler::class, 'registerRoute']],
                ['template_redirect', [OrganizerConfirmationRouteHandler::class, 'handle']],
            ],
            $hooks
        );
    }
}
