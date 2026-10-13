<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Site;

/**
 * Empêche l'affichage de la fiche énigmes tant que le joueur n'est pas engagé.
 */
final class PlayerEnigmasAccessHandler
{
    public const INTENT_QUERY = 'cat_enigmes';

    public static function register(callable $addAction, callable $addFilter): void
    {
        $addAction('template_redirect', [self::class, 'guardHuntPage'], 1);
        $addFilter('login_redirect', [self::class, 'filterLoginRedirect'], 20, 3);
        $addFilter('login_message', [self::class, 'filterLoginMessage'], 11);
    }

    public static function destination(int $huntId, int $userId): string
    {
        static $cache = [];
        $cacheKey = $huntId . ':' . $userId;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $policy = new PlayerEnigmasAccessPolicy();
        $primaryId = function_exists('cat_get_primary_hunt_id') ? cat_get_primary_hunt_id() : 0;
        $publication = function_exists('get_post_status') ? (string) get_post_status($huntId) : '';
        $validation = self::fieldValue($huntId, 'chasse_cache_statut_validation');
        $business = self::fieldValue($huntId, 'chasse_cache_statut');
        $cost = (int) self::fieldValue($huntId, 'chasse_infos_cout_points');
        $cache[$cacheKey] = $policy->destination(
            function_exists('cat_is_single_hunt_mode') && cat_is_single_hunt_mode(),
            $huntId > 0 && $huntId === $primaryId,
            $policy->isPlayable($publication, $validation, $business, $cost),
            self::isPrivileged($userId, $huntId),
            $userId > 0,
            $userId > 0
                && function_exists('utilisateur_est_engage_dans_chasse')
                && utilisateur_est_engage_dans_chasse($userId, $huntId)
        );

        return $cache[$cacheKey];
    }

    public static function menuVisible(int $huntId, int $userId): bool
    {
        return (new PlayerEnigmasAccessPolicy())->showMenu(self::destination($huntId, $userId));
    }

    /**
     * URL finale pour un accès direct refusé, ou chaîne vide si l'accès reste ouvert.
     */
    public static function entryRedirectUrl(int $huntId, int $userId): string
    {
        $destination = self::destination($huntId, $userId);
        if ($destination === PlayerEnigmasAccessPolicy::LOGIN) {
            return self::directLoginUrl();
        }
        if ($destination === PlayerEnigmasAccessPolicy::HOME) {
            return self::homeUrlWithRequestError();
        }

        return '';
    }

    public static function enigmasUrl(int $huntId): string
    {
        $permalink = function_exists('get_permalink') ? get_permalink($huntId) : '';
        $base = is_string($permalink) && $permalink !== '' ? $permalink : home_url('/');

        return $base . '#chasse-enigmes-wrapper';
    }

    public static function directLoginUrl(): string
    {
        $return = add_query_arg(self::INTENT_QUERY, '1', home_url('/'));

        return wp_login_url($return);
    }

    public static function joinLoginUrl(): string
    {
        return wp_login_url(home_url('/'));
    }

    /**
     * Après un engagement, la chasse principale renvoie vers les énigmes.
     * Un échec pour un joueur non engagé s'affiche sur l'accueil.
     */
    public static function engagementRedirectUrl(
        int $huntId,
        int $userId,
        string $status,
        string $defaultUrl
    ): string {
        if (!function_exists('cat_is_single_hunt_mode') || !cat_is_single_hunt_mode()) {
            return $defaultUrl;
        }
        $primaryId = function_exists('cat_get_primary_hunt_id') ? cat_get_primary_hunt_id() : 0;
        if ($primaryId <= 0 || $primaryId !== $huntId) {
            return $defaultUrl;
        }
        if ($status === 'success') {
            return self::enigmasUrl($huntId);
        }
        if (self::destination($huntId, $userId) !== PlayerEnigmasAccessPolicy::HOME) {
            return $defaultUrl;
        }

        $code = $status === 'points_insuffisants' ? 'points_insuffisants' : 'engagement';

        return add_query_arg('erreur', $code, home_url('/'));
    }

    public static function guardHuntPage(): void
    {
        if (!is_singular('chasse')) {
            return;
        }

        $url = self::entryRedirectUrl((int) get_queried_object_id(), (int) get_current_user_id());
        if ($url === '') {
            return;
        }

        wp_safe_redirect($url);
        exit;
    }

    /**
     * @param mixed $user
     * @return mixed
     */
    public static function filterLoginRedirect($redirectTo, $requested, $user)
    {
        $requestedUrl = is_string($requested) ? $requested : '';
        if (!self::isEnigmasIntent($requestedUrl) || !is_object($user) || !isset($user->ID)) {
            return $redirectTo;
        }

        $huntId = function_exists('cat_get_primary_hunt_id') ? cat_get_primary_hunt_id() : 0;
        $destination = self::destination($huntId, (int) $user->ID);
        if ($destination === PlayerEnigmasAccessPolicy::ALLOW) {
            return self::enigmasUrl($huntId);
        }

        return home_url('/');
    }

    public static function filterLoginMessage(string $message): string
    {
        $redirect = isset($_REQUEST['redirect_to'])
            ? (string) wp_unslash($_REQUEST['redirect_to'])
            : '';
        if (!self::isEnigmasIntent($redirect)) {
            return $message;
        }

        $huntId = function_exists('cat_get_primary_hunt_id') ? cat_get_primary_hunt_id() : 0;
        $title = $huntId > 0 && function_exists('get_the_title') ? (string) get_the_title($huntId) : '';
        $text = $title !== ''
            ? sprintf(
                /* translators: %s: hunt title. */
                __('Connectez-vous pour accéder aux énigmes de %s.', 'chassesautresor-com'),
                $title
            )
            : __('Connectez-vous pour accéder aux énigmes.', 'chassesautresor-com');

        return $message . '<p class="message">' . esc_html($text) . '</p>';
    }

    public static function homeUrlWithRequestError(): string
    {
        $url = home_url('/');
        if (!isset($_GET['erreur'])) {
            return $url;
        }

        $code = sanitize_text_field(wp_unslash((string) $_GET['erreur']));
        if (!in_array($code, ['points_insuffisants', 'engagement'], true)) {
            return $url;
        }

        return add_query_arg('erreur', $code, $url);
    }

    private static function isEnigmasIntent(string $url): bool
    {
        $query = (string) wp_parse_url($url, PHP_URL_QUERY);
        if ($query === '') {
            return false;
        }

        $args = [];
        parse_str($query, $args);

        return isset($args[self::INTENT_QUERY]) && (string) $args[self::INTENT_QUERY] === '1';
    }

    private static function isPrivileged(int $userId, int $huntId): bool
    {
        if ($userId <= 0 || $huntId <= 0) {
            return false;
        }
        if (function_exists('user_can') && user_can($userId, 'manage_options')) {
            return true;
        }

        return function_exists('utilisateur_est_organisateur_associe_a_chasse')
            && utilisateur_est_organisateur_associe_a_chasse($userId, $huntId);
    }

    private static function fieldValue(int $huntId, string $field): string
    {
        if ($huntId <= 0 || !function_exists('get_field')) {
            return '';
        }

        $value = get_field($field, $huntId);

        return is_scalar($value) ? (string) $value : '';
    }
}
