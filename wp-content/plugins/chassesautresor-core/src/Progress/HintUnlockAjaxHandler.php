<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\HintAccessPolicy;
use ChassesAuTresor\Core\Points\PointsRepository;
use ChassesAuTresor\Core\Points\PointsService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Secure and serialize hint unlock requests. */
class HintUnlockAjaxHandler {
    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_debloquer_indice', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_debloquer_indice', [self::class, 'handle']);
    }

    public static function configure(callable $renderer): void {
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        global $wpdb;

        $hintId = isset($_POST['indice_id']) ? (int) $_POST['indice_id'] : 0;
        $userId = (int) get_current_user_id();
        $cost = $hintId > 0 ? max(0, (int) get_field('indice_cout_points', $hintId)) : 0;
        $points = new PointsService(new PointsRepository($wpdb));
        $error = (new HintUnlockPolicy())->validate(
            is_user_logged_in(),
            wp_verify_nonce((string) ($_POST['nonce'] ?? ''), 'unlock_hint') !== false,
            $hintId,
            $hintId > 0 ? (string) get_post_type($hintId) : '',
            $cost,
            $userId > 0 ? $points->getBalance($userId) : 0
        );
        if ($error !== null) {
            $status = in_array($error, ['non_connecte', 'invalid_nonce', 'points_insuffisants'], true) ? 403 : 400;
            wp_send_json_error($error, $status);
        }
        $service = new HintUnlockService(new HintUnlockRepository($wpdb), $points);
        $alreadyUnlocked = $service->isUnlocked($userId, $hintId);
        $now = time();
        $contextRiddleId = self::contextRiddleId($hintId);
        if (!$alreadyUnlocked && !(new HintPlayerGateService(
            new HintAccessPolicy(),
            new HintUnlockRepository($wpdb),
            new RiddleEngagementRepository($wpdb),
            new HuntEngagementRepository($wpdb)
        ))->canConsult($userId, $hintId, $now, $contextRiddleId)) {
            wp_send_json_error([
                'code' => 'indice_indisponible',
                'hints_html' => self::hintsMarkup($hintId, $userId),
            ], 403);
        }
        $lockKey = "hint_unlock_{$hintId}_{$userId}";
        if (!wp_cache_add($lockKey, 1, 'indices', 15)) {
            wp_send_json_error('doublon', 409);
        }
        if (!$alreadyUnlocked) {
            [$huntId, $riddleId] = self::relations($hintId);
            $saved = $service->recordUnlock(
                $userId,
                $hintId,
                $huntId ?: null,
                $riddleId ?: null,
                $cost,
                current_time('mysql', true),
                __('Déblocage indice', 'chassesautresor-com')
            );
            if (!$saved) {
                wp_cache_delete($lockKey, 'indices');
                wp_send_json_error('erreur_interne', 500);
            }
        }
        wp_cache_delete($lockKey, 'indices');

        $renderer = self::$renderer ?? [new HintUnlockRenderer(), 'render'];
        wp_send_json_success([
            'html' => (string) call_user_func($renderer, $hintId),
            'points' => $points->getBalance($userId),
            'message' => esc_html__('Indice débloqué', 'chassesautresor-com'),
            'hints_html' => self::hintsMarkup($hintId, $userId),
        ]);
    }

    private static function contextRiddleId(int $hintId): int
    {
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        if ($hintId <= 0 || $riddleId <= 0) {
            return 0;
        }

        $ids = (new RiddleParticipationService())->hintIds($riddleId);
        $known = array_merge($ids['riddle'], $ids['hunt']);

        return in_array($hintId, $known, true) ? $riddleId : 0;
    }

    private static function hintsMarkup(int $hintId, int $userId): string
    {
        $riddleId = self::contextRiddleId($hintId);
        if ($userId <= 0 || $riddleId <= 0) {
            return '';
        }

        return (new RiddlePlayerPanelRenderer())->renderHintLists($riddleId, $userId);
    }

    /** @return array{int,int} */
    private static function relations(int $hintId): array
    {
        return [
            self::relationshipId(get_field('indice_chasse_linked', $hintId)),
            self::relationshipId(get_field('indice_enigme_linked', $hintId)),
        ];
    }

    /** @param mixed $value */
    private static function relationshipId($value): int
    {
        $id = (new RelationshipService())->normalizeId($value);

        return $id ?? 0;
    }
}
