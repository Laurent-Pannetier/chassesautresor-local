<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/** WordPress lookups shared by attempt alerts. */
class AttemptAlertContext
{
    public function isDemoMode(): bool
    {
        return function_exists('cat_is_demo_mode') && cat_is_demo_mode();
    }

    /** @return int[] */
    public function staffUserIds(int $riddleId): array
    {
        $relationships = new RelationshipService();
        $administrators = function_exists('get_users')
            ? (array) get_users(['role' => 'administrator', 'fields' => 'ids'])
            : [];

        return array_values(array_unique($relationships->normalizeIds(array_merge(
            $administrators,
            $this->organizerUserIds($riddleId, $relationships)
        ))));
    }

    public function playerIsStaff(int $userId, int $riddleId): bool
    {
        return $userId > 0 && in_array($userId, $this->staffUserIds($riddleId), true);
    }

    public function organizerEmail(int $riddleId): string
    {
        $organizerId = $this->organizerProfileId($riddleId);
        if ($organizerId <= 0 || !function_exists('get_field')) {
            return '';
        }

        $email = get_field('email_organisateur', $organizerId);
        if (is_array($email)) {
            $email = reset($email);
        }

        return is_scalar($email) ? (string) $email : '';
    }

    public function adminEmail(): string
    {
        return function_exists('get_option') ? (string) get_option('admin_email') : '';
    }

    public function isEmail(string $email): bool
    {
        if (function_exists('is_email')) {
            return (bool) is_email($email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validationMode(int $riddleId): string
    {
        if ($riddleId <= 0 || !function_exists('get_field')) {
            return '';
        }

        $mode = get_field('enigme_mode_validation', $riddleId);
        if (function_exists('enigme_normaliser_mode_validation')) {
            return enigme_normaliser_mode_validation($mode);
        }

        return strtolower(trim(is_scalar($mode) ? (string) $mode : ''));
    }

    public function eligibleSolvedCount(int $riddleId): int
    {
        if ($riddleId <= 0 || !isset($GLOBALS['wpdb'])) {
            return 0;
        }

        return (new RiddleStatisticsRepository($GLOBALS['wpdb']))->countEligibleSolvedPlayers(
            $riddleId,
            $this->staffUserIds($riddleId)
        );
    }

    public function riddleTitle(int $riddleId): string
    {
        $title = function_exists('get_the_title') ? (string) get_the_title($riddleId) : '';
        if (function_exists('html_entity_decode')) {
            $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        }

        return $title;
    }

    /** @return array{login:string,email:string,name:string}|null */
    public function player(int $userId): ?array
    {
        if ($userId <= 0 || !function_exists('get_userdata')) {
            return null;
        }

        $user = get_userdata($userId);
        if (!$user) {
            return null;
        }

        $login = (string) $user->user_login;

        return [
            'login' => $login,
            'email' => (string) $user->user_email,
            'name' => (string) ($user->display_name ?: $login),
        ];
    }

    public function reviewUrl(int $riddleId, string $attemptUid): string
    {
        $fallback = function_exists('add_query_arg') && function_exists('home_url')
            ? (string) add_query_arg(['uid' => $attemptUid], home_url('/traitement-tentative'))
            : '';
        $reviewUrl = function_exists('apply_filters')
            ? (string) apply_filters(
                'chassesautresor_edition_url',
                $fallback,
                'enigme',
                $riddleId,
                'tentatives'
            )
            : $fallback;
        if ($reviewUrl === '') {
            $reviewUrl = $fallback;
        }
        if ($attemptUid !== '' && function_exists('add_query_arg')) {
            $reviewUrl = (string) add_query_arg('uid', $attemptUid, $reviewUrl);
        }

        return $reviewUrl;
    }

    public function riddleUrl(int $riddleId): string
    {
        return function_exists('get_permalink') ? (string) get_permalink($riddleId) : '';
    }

    /** @return int[] */
    private function organizerUserIds(int $riddleId, RelationshipService $relationships): array
    {
        $organizerId = $this->organizerProfileId($riddleId);
        if ($organizerId <= 0 || !function_exists('get_field')) {
            return [];
        }

        return $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));
    }

    private function organizerProfileId(int $riddleId): int
    {
        if ($riddleId <= 0 || !function_exists('get_field')) {
            return 0;
        }

        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId, false));
        if ($huntId === null) {
            return 0;
        }

        return $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId)) ?? 0;
    }
}
