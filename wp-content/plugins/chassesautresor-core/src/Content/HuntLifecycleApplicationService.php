<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Messages\HuntCorrectionMessageService;
use ChassesAuTresor\Core\Progress\HuntStatusUpdater;
use ChassesAuTresor\Core\Progress\RiddleSystemStateUpdater;
use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;
use WP_User;

/**
 * Apply Edit / Activate lifecycle mutations for the profile switch.
 */
final class HuntLifecycleApplicationService
{
    private HuntLifecycleService $lifecycle;
    private HuntValidationAccessResolver $accessResolver;
    private HuntModerationService $moderation;

    public function __construct(
        ?HuntLifecycleService $lifecycle = null,
        ?HuntValidationAccessResolver $accessResolver = null,
        ?HuntModerationService $moderation = null
    ) {
        $this->lifecycle = $lifecycle ?? new HuntLifecycleService();
        $this->accessResolver = $accessResolver ?? new HuntValidationAccessResolver();
        $this->moderation = $moderation ?? new HuntModerationService();
    }

    /**
     * @return array{
     *   hunt_id:int,
     *   state:string,
     *   checked:bool,
     *   can_activate:bool,
     *   can_deactivate:bool,
     *   disabled:bool,
     *   status_label:string,
     *   badge_label:string,
     *   badge_icon:string,
     *   help:string
     * }
     */
    public function getViewModel(int $huntId, int $userId, bool $isDemoMode): array
    {
        $validation = (string) get_field('chasse_cache_statut_validation', $huntId);
        $state = $this->lifecycle->resolveState($validation);
        $isAdmin = user_can($userId, 'administrator');
        $isOrg = $this->isOrganizerAssociated($userId, $huntId);
        $canRequest = $this->accessResolver->canRequest($huntId, $userId);
        $canActivate = $this->lifecycle->canActivate($isAdmin, $isOrg, $isDemoMode, $state, $canRequest);
        $canDeactivate = $this->lifecycle->canDeactivate($isAdmin, $isOrg, $isDemoMode, $state);
        $checked = $this->lifecycle->isSwitchOn($state);

        return [
            'hunt_id' => $huntId,
            'state' => $state,
            'checked' => $checked,
            'can_activate' => $canActivate,
            'can_deactivate' => $canDeactivate,
            'disabled' => !($checked ? $canDeactivate : $canActivate),
            'status_label' => $this->statusLabel($state, $isAdmin),
            'badge_label' => $this->badgeLabel($state),
            'badge_icon' => $this->lifecycle->stateIcon($state),
            'help' => $this->helpText($state, $isDemoMode, $isAdmin, $canActivate, $canDeactivate),
        ];
    }

    /**
     * @return array{ok:bool,error:?string,view:?array<string,mixed>}
     */
    public function toggle(int $huntId, int $userId, bool $activate, bool $isDemoMode): array
    {
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            return ['ok' => false, 'error' => 'hunt', 'view' => null];
        }

        $view = $this->getViewModel($huntId, $userId, $isDemoMode);
        $isAdmin = user_can($userId, 'administrator');

        if ($activate) {
            if (!$view['can_activate']) {
                return ['ok' => false, 'error' => 'forbidden', 'view' => $view];
            }
            $action = $this->lifecycle->activateAction($isDemoMode, $isAdmin, $view['state']);
            if ($action === 'request') {
                $this->requestValidation($huntId);
            } elseif ($action === 'validate') {
                $this->validateHunt($huntId);
            } else {
                return ['ok' => false, 'error' => 'action', 'view' => $view];
            }
        } else {
            if (!$view['can_deactivate']) {
                return ['ok' => false, 'error' => 'forbidden', 'view' => $view];
            }
            $action = $this->lifecycle->deactivateAction($view['state']);
            if ($action === 'cancel') {
                $this->reopenForEdition($huntId);
            } elseif ($action === 'reopen') {
                $this->reopenForEdition($huntId);
            } else {
                return ['ok' => false, 'error' => 'action', 'view' => $view];
            }
        }

        return [
            'ok' => true,
            'error' => null,
            'view' => $this->getViewModel($huntId, $userId, $isDemoMode),
        ];
    }

    public function requestValidation(int $huntId): void
    {
        (new HuntStatusUpdater())->synchronizePublication($huntId, 'en_attente');
        update_field('chasse_cache_statut', 'revision', $huntId);
        (new HuntCorrectionMessageService())->clear($huntId);
    }

    public function validateHunt(int $huntId): void
    {
        $plan = $this->moderation->plan('valider');
        $riddleIds = array_map(
            'intval',
            (array) get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId))
        );
        $organizerId = (new RelationshipService())->normalizeId(
            get_field('chasse_cache_organisateur', $huntId)
        ) ?? 0;
        $users = $organizerId > 0 ? (array) get_field('utilisateurs_associes', $organizerId) : [];
        $userIds = [];
        foreach ($users as $user) {
            $userIds[] = is_object($user) && isset($user->ID) ? (int) $user->ID : (int) $user;
        }
        $userIds = array_values(array_filter($userIds));

        (new HuntModerationMutationService())->apply(
            $huntId,
            $riddleIds,
            (array) (get_field('champs_caches', $huntId) ?: []),
            $plan,
            'wp_update_post',
            'update_field',
            static fn (int $id): string => (new HuntStatusUpdater())->refresh($id),
            static fn (int $id): string => (new RiddleSystemStateUpdater())->refresh($id)
        );

        (new HuntModerationOrganizerService())->promote(
            $organizerId,
            $userIds,
            'get_post_status',
            static function (int $id): void {
                wp_update_post(['ID' => $id, 'post_status' => 'publish']);
            },
            static function (int $uid): void {
                $user = new WP_User($uid);
                $user->add_role(ROLE_ORGANISATEUR);
                $user->remove_role(ROLE_ORGANISATEUR_CREATION);
            }
        );

        global $wpdb;
        (new HuntModerationNotificationService(CoreServiceFactory::accountMessages($wpdb)))->notify(
            'valider',
            $organizerId,
            $huntId,
            $userIds,
            (string) get_the_title($huntId),
            (string) get_permalink($huntId),
            ''
        );
    }

    public function reopenForEdition(int $huntId): void
    {
        $plan = $this->moderation->plan('correction');
        $riddleIds = array_map(
            'intval',
            (array) get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId))
        );

        (new HuntModerationMutationService())->apply(
            $huntId,
            $riddleIds,
            (array) (get_field('champs_caches', $huntId) ?: []),
            $plan,
            'wp_update_post',
            'update_field',
            static fn (int $id): string => (new HuntStatusUpdater())->refresh($id),
            static fn (int $id): string => (new RiddleSystemStateUpdater())->refresh($id)
        );

        update_field('chasse_cache_statut', 'revision', $huntId);
        (new HuntCorrectionMessageService())->clear($huntId);
    }

    private function isOrganizerAssociated(int $userId, int $huntId): bool
    {
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId === null) {
            return false;
        }

        return in_array(
            $userId,
            $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId)),
            true
        );
    }

    private function statusLabel(string $state, bool $isAdmin): string
    {
        return match ($state) {
            HuntLifecycleService::STATE_ACTIVE => __('Active', 'chassesautresor-com'),
            HuntLifecycleService::STATE_PENDING => $isAdmin
                ? __('Demande en attente — activer pour valider', 'chassesautresor-com')
                : __('Demande de validation en attente', 'chassesautresor-com'),
            HuntLifecycleService::STATE_EDITABLE => __('Éditable', 'chassesautresor-com'),
            default => __('Non disponible', 'chassesautresor-com'),
        };
    }

    private function badgeLabel(string $state): string
    {
        return match ($state) {
            HuntLifecycleService::STATE_ACTIVE => __('Active', 'chassesautresor-com'),
            HuntLifecycleService::STATE_PENDING => __('En attente', 'chassesautresor-com'),
            HuntLifecycleService::STATE_EDITABLE => __('Éditable', 'chassesautresor-com'),
            default => __('Indisponible', 'chassesautresor-com'),
        };
    }

    private function helpText(
        string $state,
        bool $isDemoMode,
        bool $isAdmin,
        bool $canActivate,
        bool $canDeactivate
    ): string {
        if ($state === HuntLifecycleService::STATE_PENDING && $isAdmin) {
            return __('Une demande de validation est prête. Passez le switch sur Activer pour publier.', 'chassesautresor-com');
        }
        if ($state === HuntLifecycleService::STATE_PENDING) {
            return __('Votre demande est en cours de traitement. Vous pouvez annuler pour revenir en édition.', 'chassesautresor-com');
        }
        if ($isDemoMode && $canActivate) {
            return __('Mode démo : l’activation publie directement sans demande de validation.', 'chassesautresor-com');
        }
        if ($canActivate) {
            return __('Activer envoie une demande de validation à l’administrateur.', 'chassesautresor-com');
        }
        if ($canDeactivate && $state === HuntLifecycleService::STATE_ACTIVE) {
            return __('Repasser en édition rouvre les panneaux de modification.', 'chassesautresor-com');
        }
        if ($state === HuntLifecycleService::STATE_EDITABLE && !$canActivate) {
            return __('Complétez la chasse et ses énigmes pour pouvoir activer.', 'chassesautresor-com');
        }

        return '';
    }
}
