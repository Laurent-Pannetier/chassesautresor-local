<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/**
 * Validate and paginate attempt lists while delegating HTML rendering to the theme.
 */
class RiddleAttemptListAjaxHandler {
    /** @var callable|null */
    private static $canModify;

    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_lister_tentatives_enigme', [self::class, 'handle']);
    }

    public static function configure(
        callable $canModify,
        callable $renderer
    ): void {
        self::$canModify = $canModify;
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $dependenciesReady = is_callable(self::$canModify)
            && is_callable(self::$renderer);
        $canModify = $dependenciesReady && is_user_logged_in() && $riddleId > 0
            ? (bool) call_user_func(self::$canModify, $riddleId)
            : false;
        $request = (new RiddleAttemptListRequestService())->prepare(
            is_user_logged_in(),
            $riddleId,
            $riddleId > 0 ? (string) get_post_type($riddleId) : '',
            $canModify,
            (int) ($_POST['page'] ?? 1)
        );
        if ($request['error'] !== null) {
            wp_send_json_error($request['error']);
        }
        if (!$dependenciesReady) {
            wp_send_json_error('acces_refuse');
        }

        global $wpdb;
        $attemptService = CoreServiceFactory::riddleAttempts($wpdb);
        $attempts = $attemptService->findForRiddle($riddleId, $request['per_page'], $request['offset']);
        $total = $attemptService->countForRiddle($riddleId);
        $pages = (int) ceil($total / $request['per_page']);
        $html = (string) call_user_func(self::$renderer, [
            'tentatives' => $attempts,
            'page' => $request['page'],
            'par_page' => $request['per_page'],
            'total' => $total,
            'pages' => $pages,
        ]);

        wp_send_json_success([
            'html' => $html,
            'total' => $total,
            'page' => $request['page'],
            'pages' => $pages,
        ]);
    }
}
