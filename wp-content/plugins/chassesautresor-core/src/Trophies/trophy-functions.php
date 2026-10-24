<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationService;
use ChassesAuTresor\Core\Teams\TeamRepository;
use ChassesAuTresor\Core\Trophies\TrophyAwardRepository;
use ChassesAuTresor\Core\Trophies\TrophyAwardService;
use ChassesAuTresor\Core\Trophies\TrophyDefinitionRepository;
use ChassesAuTresor\Core\Trophies\TrophyEvent;
use ChassesAuTresor\Core\Trophies\TrophyEventDispatcher;
use ChassesAuTresor\Core\Trophies\TrophyCreateFormRenderer;
use ChassesAuTresor\Core\Trophies\TrophyTriggerRegistry;
use ChassesAuTresor\Core\Trophies\TrophyWallCatalog;

if (!function_exists('cat_trophy_award_repository')) {
    function cat_trophy_award_repository(): TrophyAwardRepository
    {
        global $wpdb;

        return new TrophyAwardRepository($wpdb);
    }
}

if (!function_exists('cat_trophy_team_repository')) {
    function cat_trophy_team_repository(): TeamRepository
    {
        global $wpdb;

        return new TeamRepository($wpdb);
    }
}

if (!function_exists('cat_trophy_award_service')) {
    function cat_trophy_award_service(): TrophyAwardService
    {
        $notifications = function_exists('cat_notification_service')
            ? cat_notification_service()
            : new NotificationService(cat_notification_repository());

        return new TrophyAwardService(
            cat_trophy_award_repository(),
            $notifications,
            cat_trophy_team_repository()
        );
    }
}

if (!function_exists('cat_dispatch_trophy_event')) {
    /**
     * Public extension point used by future application code.
     *
     * @param array<string, mixed> $context
     * @return array<int, int[]>
     */
    function cat_dispatch_trophy_event(string $eventKey, int $userId, array $context = []): array
    {
        $dispatcher = new TrophyEventDispatcher(
            new TrophyDefinitionRepository(),
            new TrophyTriggerRegistry(),
            cat_trophy_award_service()
        );

        return $dispatcher->dispatch(new TrophyEvent('code_event', $userId, $eventKey, $context));
    }
}

if (!function_exists('cat_user_trophy_count')) {
    function cat_user_trophy_count(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $teamId = cat_trophy_team_repository()->activeTeamId($userId);

        return cat_trophy_award_repository()->countForRecipients($userId, $teamId);
    }
}

if (!function_exists('cat_user_trophies')) {
    /** @return array<int, array<string, mixed>> */
    function cat_user_trophies(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $teamId = cat_trophy_team_repository()->activeTeamId($userId);
        $rows = cat_trophy_award_repository()->listForRecipients($userId, $teamId);
        $items = [];
        foreach ($rows as $row) {
            $trophyId = (int) ($row['trophy_id'] ?? 0);
            if ($trophyId <= 0) {
                continue;
            }
            if (!isset($items[$trophyId])) {
                $context = json_decode((string) ($row['context'] ?? ''), true);
                $snapshot = is_array($context['trophy_snapshot'] ?? null)
                    ? $context['trophy_snapshot']
                    : [];
                $imageId = (int) ($snapshot['image_id'] ?? get_post_thumbnail_id($trophyId));
                $image = function_exists('wp_get_attachment_image_url')
                    ? wp_get_attachment_image_url($imageId, 'medium')
                    : '';
                $items[$trophyId] = [
                    'id' => $trophyId,
                    'title' => (string) ($snapshot['title'] ?? get_the_title($trophyId)),
                    'description' => (string) (
                        $snapshot['description'] ?? get_post_field('post_excerpt', $trophyId)
                    ),
                    'image_url' => is_string($image) ? $image : '',
                    'awarded_at' => (string) ($row['awarded_at'] ?? ''),
                    'recipient_types' => [],
                ];
            }
            $items[$trophyId]['recipient_types'][] = (string) ($row['recipient_type'] ?? 'user');
            $items[$trophyId]['recipient_types'] = array_values(array_unique(
                $items[$trophyId]['recipient_types']
            ));
        }

        return array_values($items);
    }
}

if (!function_exists('cat_trophy_wall')) {
    /**
     * Trophies visible on the organizer or administrator wall.
     *
     * @return array<int, array<string, mixed>>
     */
    function cat_trophy_wall(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $isAdmin = cat_trophy_viewer_is_admin($userId);
        $huntIds = $isAdmin ? [] : cat_trophy_organizer_hunt_ids($userId);
        $visible = [];
        foreach ((new TrophyDefinitionRepository())->active() as $trophy) {
            if ($isAdmin || ($trophy->huntId > 0 && in_array($trophy->huntId, $huntIds, true))) {
                $visible[] = $trophy;
            }
        }
        if ($visible === []) {
            return [];
        }

        $ids = array_map(static fn ($trophy): int => $trophy->id, $visible);
        $awards = cat_trophy_award_repository()->rowsForTrophies($ids);
        $userIds = [];
        $teamIds = [];
        foreach ($awards as $award) {
            $recipientId = (int) ($award['recipient_id'] ?? 0);
            if (($award['recipient_type'] ?? '') === 'team') {
                $teamIds[] = $recipientId;
            } else {
                $userIds[] = $recipientId;
            }
        }

        $cards = (new TrophyWallCatalog())->cards(
            $visible,
            $awards,
            cat_trophy_administrator_ids(),
            cat_trophy_user_names($userIds),
            cat_trophy_award_repository()->teamNames($teamIds),
            $isAdmin,
            $huntIds
        );
        foreach ($cards as $index => $card) {
            $cards[$index]['image_url'] = cat_trophy_image_url((int) ($card['image_id'] ?? 0));
            if ($isAdmin) {
                $cards[$index]['context_label'] = cat_trophy_wall_context_label(
                    (int) ($card['hunt_id'] ?? 0)
                );
                $cards[$index]['edit_url'] = cat_trophy_edit_url((int) ($card['id'] ?? 0));
            }
        }

        return $cards;
    }
}

if (!function_exists('cat_trophy_viewer_is_admin')) {
    function cat_trophy_viewer_is_admin(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (
            function_exists('cta_shell_user_is_administrator')
            && function_exists('get_current_user_id')
            && $userId === (int) get_current_user_id()
        ) {
            return cta_shell_user_is_administrator();
        }
        if (!function_exists('get_userdata')) {
            return false;
        }
        $user = get_userdata($userId);

        return $user instanceof WP_User && in_array('administrator', (array) $user->roles, true);
    }
}

if (!function_exists('cat_trophy_organizer_hunt_ids')) {
    /** @return int[] */
    function cat_trophy_organizer_hunt_ids(int $userId): array
    {
        if (
            $userId <= 0
            || !function_exists('get_organisateur_from_user')
            || !function_exists('get_chasses_de_organisateur')
        ) {
            return [];
        }

        $organizerId = (int) get_organisateur_from_user($userId);
        if ($organizerId <= 0) {
            return [];
        }

        $hunts = get_chasses_de_organisateur($organizerId);
        $raw = $hunts instanceof WP_Query ? (array) $hunts->posts : (is_array($hunts) ? $hunts : []);
        $ids = [];
        foreach ($raw as $hunt) {
            if (is_object($hunt) && isset($hunt->ID)) {
                $ids[] = (int) $hunt->ID;
            } elseif (is_numeric($hunt)) {
                $ids[] = (int) $hunt;
            }
        }

        return array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    }
}

if (!function_exists('cat_trophy_administrator_ids')) {
    /** @return int[] */
    function cat_trophy_administrator_ids(): array
    {
        if (!function_exists('get_users')) {
            return [];
        }
        $ids = get_users([
            'role' => 'administrator',
            'fields' => 'ID',
        ]);

        return array_values(array_filter(
            array_map('intval', (array) $ids),
            static fn (int $id): bool => $id > 0
        ));
    }
}

if (!function_exists('cat_trophy_user_names')) {
    /**
     * @param int[] $userIds
     * @return array<int, string>
     */
    function cat_trophy_user_names(array $userIds): array
    {
        if (!function_exists('get_userdata')) {
            return [];
        }

        $names = [];
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if ($userId <= 0) {
                continue;
            }
            $user = get_userdata($userId);
            if (!$user instanceof WP_User) {
                continue;
            }
            $name = trim((string) $user->display_name);
            if ($name === '') {
                $name = trim((string) $user->user_login);
            }
            if ($name !== '') {
                $names[$userId] = $name;
            }
        }

        return $names;
    }
}

if (!function_exists('cat_trophy_wall_context_label')) {
    function cat_trophy_wall_context_label(int $huntId): string
    {
        if ($huntId <= 0) {
            return (string) __('Global', 'chassesautresor-com');
        }
        if (
            !function_exists('get_post_type')
            || get_post_type($huntId) !== 'chasse'
            || !function_exists('get_the_title')
        ) {
            return '';
        }

        return trim((string) get_the_title($huntId));
    }
}

if (!function_exists('cat_trophy_edit_url')) {
    function cat_trophy_edit_url(int $trophyId): string
    {
        if ($trophyId <= 0 || !function_exists('admin_url')) {
            return '';
        }

        return admin_url('post.php?post=' . $trophyId . '&action=edit');
    }
}

if (!function_exists('cat_trophy_create_return_url')) {
    function cat_trophy_create_return_url(string $notice = ''): string
    {
        $url = function_exists('home_url') ? home_url('/trophees/') : '/trophees/';
        if ($notice === '' || !function_exists('add_query_arg')) {
            return $url;
        }

        return add_query_arg('trophee_notice', $notice, $url);
    }
}

if (!function_exists('cat_trophy_hunt_choices')) {
    /** @return array<int, array{id: int, title: string}> */
    function cat_trophy_hunt_choices(): array
    {
        if (!function_exists('get_posts')) {
            return [];
        }

        $posts = get_posts([
            'post_type' => 'chasse',
            'post_status' => ['publish', 'pending'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        $choices = [];
        foreach ((array) $posts as $post) {
            $id = is_object($post) ? (int) ($post->ID ?? 0) : (int) $post;
            $title = is_object($post) ? trim((string) ($post->post_title ?? '')) : '';
            if ($id > 0 && $title === '' && function_exists('get_the_title')) {
                $title = trim((string) get_the_title($id));
            }
            if ($id > 0 && $title !== '') {
                $choices[] = ['id' => $id, 'title' => $title];
            }
        }

        return $choices;
    }
}

if (!function_exists('cat_trophy_create_form')) {
    function cat_trophy_create_form(): string
    {
        $notice = '';
        if (isset($_GET['trophee_notice'])) {
            $notice = (string) $_GET['trophee_notice'];
            if (function_exists('wp_unslash')) {
                $notice = (string) wp_unslash($notice);
            }
            $notice = function_exists('sanitize_key') ? sanitize_key($notice) : strtolower($notice);
        }
        $open = $notice !== '';
        $nonce = function_exists('wp_nonce_field')
            ? (string) wp_nonce_field('cat_trophy_create', 'cat_trophy_nonce', true, false)
            : '';
        $action = function_exists('admin_url') ? admin_url('admin-post.php') : '';

        return (new TrophyCreateFormRenderer())->render(
            $action,
            $nonce,
            cat_trophy_hunt_choices(),
            $open,
            $notice
        );
    }
}

if (!function_exists('cat_trophy_image_url')) {
    function cat_trophy_image_url(int $imageId): string
    {
        if ($imageId <= 0 || !function_exists('wp_get_attachment_image_url')) {
            return '';
        }
        $url = wp_get_attachment_image_url($imageId, 'medium');

        return is_string($url) ? $url : '';
    }
}
