<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

use ChassesAuTresor\Core\Content\OrganizerCreationService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

final class OrganizerConfirmationRouteHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'registerRoute']);
        $addAction('template_redirect', [self::class, 'handle']);
    }

    public static function registerRoute(): void
    {
        add_rewrite_rule('^confirmation-organisateur/?$', 'index.php?confirmation_organisateur=1', 'top');
        add_rewrite_tag('%confirmation_organisateur%', '1');
    }

    public static function handle(): void
    {
        if (get_query_var('confirmation_organisateur') !== '1' && !is_page('confirmation-organisateur')) {
            return;
        }

        $userId = isset($_GET['user']) ? (int) $_GET['user'] : 0;
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        $organizerId = self::confirmRequest($userId, $token);
        self::clearMessages($userId);

        if ($organizerId > 0) {
            wp_set_current_user($userId);
            wp_set_auth_cookie($userId);
            wp_safe_redirect(add_query_arg('confirmation', '1', get_permalink($organizerId)));
            exit;
        }

        wp_safe_redirect(home_url('/devenir-organisateur'));
        exit;
    }

    private static function confirmRequest(int $userId, string $token): int
    {
        global $wpdb;

        $lifecycle = new OrganizerRequestLifecycleService();
        $organizerId = $lifecycle->confirm(
            $userId,
            $token,
            static function (int $confirmedUserId) use ($wpdb): int {
                $existingId = (new OrganizerRepository($wpdb))->findIdForUser($confirmedUserId) ?? 0;
                $user = get_userdata($confirmedUserId);
                $result = (new OrganizerCreationService())->create(
                    $confirmedUserId,
                    $existingId,
                    __('Votre nom d’organisateur', 'chassesautresor-com'),
                    $user ? (string) $user->user_email : '',
                    'wp_insert_post',
                    'update_field',
                    'is_wp_error'
                );

                return (int) ($result['organizer_id'] ?? 0);
            },
            static function (int $confirmedUserId): void {
                $role = defined('ROLE_ORGANISATEUR_CREATION')
                    ? ROLE_ORGANISATEUR_CREATION
                    : 'organisateur_creation';
                (new \WP_User($confirmedUserId))->add_role($role);
            }
        );

        return (int) $organizerId;
    }

    private static function clearMessages(int $userId): void
    {
        global $wpdb;

        CoreServiceFactory::siteMessages($wpdb)->removeByKey('profil_verification');
        CoreServiceFactory::accountMessages($wpdb)->removePersistent($userId, 'profil_verification');
    }
}
