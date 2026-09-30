<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Own the complete WordPress lifecycle used to create a solution.
 */
class SolutionCreationRouteHandler {
    public static function register(callable $addAction): void {
        $addAction('init', [SolutionRouteRegistrar::class, 'register'], 10, 1);
        $addAction('init', [SolutionRouteRegistrar::class, 'maybeFlush'], 20, 1);
        $addAction('template_redirect', [self::class, 'handle'], 10, 1);
    }

    /** @return int|\WP_Error */
    public static function create(
        int $targetId,
        string $targetType,
        ?int $userId = null,
        ?callable $canManage = null,
        ?callable $findHuntId = null
    ) {
        $service = new SolutionCreationService();
        $supportedType = $service->isSupportedTargetType($targetType);
        $targetMatches = $supportedType && get_post_type($targetId) === $targetType;
        $authenticated = is_user_logged_in();
        $canManage = $canManage ?? static fn (string $type, int $id): bool => (bool) apply_filters(
            'chassesautresor_can_manage_solution',
            false,
            'create',
            $type,
            $id
        );
        $canCreate = $targetMatches && $authenticated && $canManage($targetType, $targetId);
        $findHuntId = $findHuntId ?? static fn (int $riddleId): ?int =>
            (new RelationshipService())->normalizeId(get_field('enigme_chasse_associee', $riddleId));
        $huntId = $targetMatches && $targetType === 'enigme'
            ? $findHuntId($targetId)
            : ($targetMatches ? $targetId : null);
        $existing = $canCreate && $huntId !== null
            ? get_posts((new SolutionQueryService())->getExistingSolutionIdsQueryArgs($targetId, $targetType))
            : [];
        $error = $service->getCreationError(
            $supportedType,
            $targetMatches,
            $authenticated,
            $canCreate,
            $huntId !== null,
            $existing !== []
        );
        if ($error !== null) {
            $messages = [
                'type_invalide' => __('Type de cible invalide.', 'chassesautresor-com'),
                'cible_invalide' => __('ID cible invalide.', 'chassesautresor-com'),
                'non_connecte' => __('Utilisateur non connecté.', 'chassesautresor-com'),
                'permission_refusee' => __('Droits insuffisants.', 'chassesautresor-com'),
                'existe_deja' => __('Une solution existe déjà pour cet objet.', 'chassesautresor-com'),
            ];
            return new \WP_Error($error, $messages[$error]);
        }

        return (new SolutionPostFactory())->create(
            $targetId,
            $targetType,
            (int) $huntId,
            $userId ?? get_current_user_id(),
            defined('TITRE_DEFAUT_SOLUTION') ? TITRE_DEFAUT_SOLUTION : __('Solution', 'chassesautresor-com'),
            __('Solution | %s', 'chassesautresor-com'),
            defined('SOLUTION_STATE_DESACTIVE') ? SOLUTION_STATE_DESACTIVE : 'desactive'
        );
    }

    public static function handle(): void {
        if (get_query_var('creer_solution') !== '1') {
            return;
        }
        $nonce = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'creer_solution')) {
            wp_die(__('Action non autorisée.', 'chassesautresor-com'), __('Erreur', 'chassesautresor-com'), ['response' => 403]);
        }
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url());
            exit;
        }
        $target = (new SolutionCreationService())->resolveRequestedTarget(
            isset($_GET['chasse_id']) ? absint($_GET['chasse_id']) : 0,
            isset($_GET['enigme_id']) ? absint($_GET['enigme_id']) : 0
        );
        if ($target === null) {
            wp_die(__('ID cible manquant.', 'chassesautresor-com'), __('Erreur', 'chassesautresor-com'), ['response' => 400]);
        }
        $solutionId = self::create($target['id'], $target['type']);
        if (is_wp_error($solutionId)) {
            $referer = wp_get_referer() ?: get_permalink($target['id']);
            wp_safe_redirect(add_query_arg('erreur', sanitize_text_field($solutionId->get_error_message()), $referer));
            exit;
        }
        wp_safe_redirect(get_permalink($target['id']));
        exit;
    }
}
