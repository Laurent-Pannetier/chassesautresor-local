<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Points\PointsRepository;
use ChassesAuTresor\Core\Points\PointsService;

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
        if (!is_callable(self::$renderer)) {
            wp_send_json_error('erreur_interne', 500);
        }

        $lockKey = "hint_unlock_{$hintId}_{$userId}";
        if (!wp_cache_add($lockKey, 1, 'indices', 15)) {
            wp_send_json_error('doublon', 409);
        }
        $service = new HintUnlockService(new HintUnlockRepository($wpdb), $points);
        if (!$service->isUnlocked($userId, $hintId)) {
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

        wp_send_json_success([
            'html' => (string) call_user_func(self::$renderer, $hintId),
            'points' => $points->getBalance($userId),
            'message' => esc_html__('Indice débloqué', 'chassesautresor-com'),
        ]);
    }

    /** @return array{int,int} */
    private static function relations(int $hintId): array {
        $hunt = get_field('indice_chasse_linked', $hintId);
        if (is_array($hunt)) {
            $first = $hunt[0] ?? null;
            $huntId = is_array($first) ? (int) ($first['ID'] ?? 0) : (int) $first;
        } else {
            $huntId = is_object($hunt) ? (int) ($hunt->ID ?? 0) : (int) $hunt;
        }

        return [$huntId, (int) get_field('indice_enigme_linked', $hintId)];
    }
}
