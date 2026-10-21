<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Form posts from the Équipe page. */
final class TeamRequestHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['cat_equipe_action'])) {
            return;
        }
        if (!function_exists('is_user_logged_in') || !is_user_logged_in()) {
            self::sendToLogin();
        }

        $nonce = isset($_POST['cat_equipe_nonce'])
            ? sanitize_text_field(wp_unslash((string) $_POST['cat_equipe_nonce']))
            : '';
        if (!wp_verify_nonce($nonce, 'cat_equipe')) {
            self::redirect('forbidden');
        }

        $action = sanitize_key(wp_unslash((string) $_POST['cat_equipe_action']));
        $actor = cat_team_actor();
        if ($actor->userId <= 0) {
            self::sendToLogin();
        }

        try {
            self::dispatch($action, $actor);
        } catch (\Throwable) {
            self::redirect('busy');
        }
    }

    private static function dispatch(string $action, TeamActor $actor): void
    {
        $service = cat_team_service();
        if ($action === 'set_max') {
            self::saveMax($actor);

            return;
        }
        if ($action === 'join') {
            self::join($service, $actor);

            return;
        }

        $upload = ['id' => null, 'error' => false];
        if ($action === 'create' || $action === 'update') {
            $upload = self::upload();
            if ($upload['error']) {
                self::redirect('invalid_image');
            }
        }

        $result = match ($action) {
            'create' => $service->create($actor, self::text('equipe_nom'), $upload['id']),
            'leave' => $service->leave($actor),
            'exclude' => $service->exclude($actor, self::integer('equipe_id'), self::integer('user_id')),
            'update' => $service->updateProfile(
                $actor,
                self::integer('equipe_id'),
                self::text('equipe_nom'),
                $upload['id'],
                $upload['id'] !== null || isset($_POST['equipe_remove_image'])
            ),
            'regenerate' => $service->regenerate($actor, self::integer('equipe_id')),
            default => TeamResult::error('forbidden'),
        };
        if (!$result->success) {
            self::discard($upload['id']);
        }
        self::redirect($result->code);
    }

    private static function join(TeamService $service, TeamActor $actor): void
    {
        $throttle = self::throttle();
        if (!$throttle->allows($actor->userId)) {
            self::redirect('rate_limited');
        }

        $result = $service->join($actor, self::text('equipe_code'));
        if (in_array($result->code, ['invalid_code', 'full', 'blocked'], true)) {
            $throttle->recordFailure($actor->userId);
        } elseif ($result->success) {
            $throttle->reset($actor->userId);
        }
        self::redirect($result->code);
    }

    private static function saveMax(TeamActor $actor): void
    {
        if (!$actor->manager) {
            self::redirect('forbidden');
        }

        $accepted = (new TeamSettings())->accept(self::integer('equipe_max'));
        if ($accepted === null) {
            self::redirect('invalid_max');
        }

        update_option(TeamSettings::OPTION, $accepted);
        self::redirect('max_updated');
    }

    /** @return array{id: ?int, error: bool} */
    private static function upload(): array
    {
        $file = $_FILES['equipe_image'] ?? null;

        return (new TeamImageUploader())->store(is_array($file) ? $file : null);
    }

    private static function throttle(): TeamJoinThrottle
    {
        $ttl = defined('MINUTE_IN_SECONDS') ? 10 * MINUTE_IN_SECONDS : 600;

        return new TeamJoinThrottle(
            static function (int $userId): int {
                $value = get_transient('cat_equipe_join_' . $userId);

                return is_numeric($value) ? (int) $value : 0;
            },
            static function (int $userId, int $count) use ($ttl): void {
                set_transient('cat_equipe_join_' . $userId, $count, $ttl);
            }
        );
    }

    private static function text(string $key): string
    {
        if (!isset($_POST[$key])) {
            return '';
        }

        return sanitize_text_field(wp_unslash((string) $_POST[$key]));
    }

    private static function integer(string $key): int
    {
        if (!isset($_POST[$key])) {
            return 0;
        }

        return absint(wp_unslash((string) $_POST[$key]));
    }

    private static function discard(?int $imageId): void
    {
        if ($imageId === null || $imageId <= 0 || !function_exists('wp_delete_attachment')) {
            return;
        }

        wp_delete_attachment($imageId, true);
    }

    private static function sendToLogin(): void
    {
        $target = function_exists('wp_login_url')
            ? wp_login_url(home_url('/equipe/'))
            : home_url('/equipe/');
        wp_safe_redirect($target);
        exit;
    }

    private static function redirect(string $code): void
    {
        wp_safe_redirect(add_query_arg('equipe_notice', $code, home_url('/equipe/')));
        exit;
    }
}
