<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Own the complete WordPress lifecycle used to create a hint.
 */
class HintCreationRouteHandler {
    public static function register(callable $addAction): void {
        $addAction('init', [HintRouteRegistrar::class, 'register'], 10, 1);
        $addAction('init', [HintRouteRegistrar::class, 'maybeFlush'], 20, 1);
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
        $service = new HintCreationService();
        $supportedType = $service->isSupportedTargetType($targetType);
        $targetMatches = $supportedType && get_post_type($targetId) === $targetType;
        $authenticated = is_user_logged_in();
        $canManage = $canManage
            ?? static fn (string $type, int $id): bool => (new RelatedContentAccessResolver())->canPerform(
                'create',
                $type,
                $id
            );
        $canCreate = $targetMatches && $authenticated && $canManage($targetType, $targetId);
        $relationships = new RelationshipService();
        $findHuntId = $findHuntId ?? static fn (int $riddleId): ?int => $relationships->normalizeId(
            get_field('enigme_chasse_associee', $riddleId)
        );
        $huntId = $targetMatches && $targetType === 'enigme'
            ? $findHuntId($targetId)
            : ($targetMatches ? $targetId : null);
        $canModifyHunt = $canCreate && $huntId !== null && $canManage('chasse', $huntId);
        $error = $service->getCreationError(
            $supportedType,
            $targetMatches,
            $authenticated,
            $canCreate,
            $huntId !== null,
            $canModifyHunt
        );

        if ($error !== null) {
            $messages = [
                'type_invalide' => __('Type de cible invalide.', 'chassesautresor-com'),
                'cible_invalide' => __('ID cible invalide.', 'chassesautresor-com'),
                'non_connecte' => __('Utilisateur non connecté.', 'chassesautresor-com'),
                'permission_refusee' => __('Droits insuffisants.', 'chassesautresor-com'),
            ];
            return new \WP_Error($error, $messages[$error]);
        }

        $query = (new HintQueryService())->getRankedHintIdsQueryArgs((int) $huntId, 'chasse');
        $rank = count($query === [] ? [] : get_posts($query)) + 1;
        $slug = (string) get_post_field('post_name', (int) $huntId);
        $generatedSlug = $slug === '' ? sanitize_title((string) get_post_field('post_title', (int) $huntId)) : '';
        $title = (new HintTitleService())->buildPlaceholder(
            defined('INDICE_DEFAULT_PREFIX') ? INDICE_DEFAULT_PREFIX : 'clue-',
            $slug,
            $generatedSlug
        );
        $hintId = (new HintPostFactory())->create(
            $targetId,
            $targetType,
            (int) $huntId,
            $userId ?? get_current_user_id(),
            $rank,
            $title,
            (int) current_time('timestamp'),
            DAY_IN_SECONDS
        );
        if (!is_wp_error($hintId)) {
            (new HintOrderingApplicationService())->applyTarget($targetId, $targetType);
        }

        return $hintId;
    }

    public static function handle(): void {
        if (get_query_var('creer_indice') !== '1') {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
        $hasValidNonce = (bool) wp_verify_nonce($nonce, 'creer_indice');
        $isLoggedIn = $hasValidNonce && is_user_logged_in();
        $request = new HintCreationRequestService();
        $target = $isLoggedIn ? $request->resolveTarget(
            isset($_GET['chasse_id']) ? absint($_GET['chasse_id']) : 0,
            isset($_GET['enigme_id']) ? absint($_GET['enigme_id']) : 0
        ) : null;
        $error = $request->getRequestError($hasValidNonce, $isLoggedIn, $target);
        if ($error === 'invalid_nonce') {
            wp_die(__('Action non autorisée.', 'chassesautresor-com'), __('Erreur', 'chassesautresor-com'), ['response' => 403]);
        }
        if ($error === 'authentication_required') {
            wp_redirect(wp_login_url());
            exit;
        }
        if ($error === 'missing_target') {
            wp_die(__('ID cible manquant.', 'chassesautresor-com'), __('Erreur', 'chassesautresor-com'), ['response' => 400]);
        }

        $hintId = self::create($target['id'], $target['type']);
        if (is_wp_error($hintId)) {
            $referer = wp_get_referer() ?: get_permalink($target['id']);
            wp_safe_redirect(add_query_arg('erreur', sanitize_text_field($hintId->get_error_message()), $referer));
            exit;
        }
        wp_safe_redirect(get_permalink($target['id']));
        exit;
    }
}
