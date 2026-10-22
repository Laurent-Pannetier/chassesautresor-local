<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Branche l'écran public à la place de wp-login.php, sans remplacer l'administration.
 */
final class SocialAuthHooks
{
    public static function register(callable $addAction, callable $addFilter): void
    {
        SocialAuthSettingsPage::register($addAction);
        SocialAuthRouteHandler::register($addAction);
        $addFilter('login_url', [self::class, 'filterLoginUrl'], 10, 3);
        $addFilter('register_url', [self::class, 'filterRegisterUrl']);
        $addFilter('login_redirect', [self::class, 'filterAccountLanding'], 40, 3);
        $addFilter('woocommerce_login_redirect', [self::class, 'filterAccountLanding'], 40, 2);
        $addFilter('registration_redirect', [self::class, 'filterAccountLanding'], 40, 1);
        $addFilter('woocommerce_registration_redirect', [self::class, 'filterAccountLanding'], 40, 1);
        $addAction('login_init', [self::class, 'redirectPublicLogin']);
        $addAction('wp_login_failed', [self::class, 'redirectFailedLogin'], 10, 2);
        $addFilter('allow_password_reset', [self::class, 'filterPasswordReset'], 10, 2);
        $addAction('password_reset', [self::class, 'clearSocialPasswordFlag']);
    }

    /**
     * @param mixed $login
     * @param mixed $redirect
     * @param mixed $forceReauth
     */
    public static function filterLoginUrl($login, $redirect = '', $forceReauth = false): string
    {
        if ($forceReauth) {
            return (string) $login;
        }

        $target = is_string($redirect) ? $redirect : '';

        return cat_social_auth_page_url($target);
    }

    /**
     * L'accueil remplace la page profil après une connexion ou une inscription.
     *
     * @param mixed $redirectTo
     * @param mixed $requested
     * @param mixed $user
     */
    public static function filterAccountLanding($redirectTo, $requested = '', $user = null): string
    {
        unset($requested, $user);
        $target = is_string($redirectTo) ? $redirectTo : '';

        return (new SocialAuthRedirect())->accept($target, home_url('/'));
    }

    /**
     * @param mixed $url
     */
    public static function filterRegisterUrl($url): string
    {
        unset($url);

        return cat_social_auth_page_url('', 'inscription');
    }

    public static function redirectPublicLogin(): void
    {
        if (self::isBackgroundRequest() || !self::isLoginScript() || self::isPost()) {
            return;
        }
        if (isset($_REQUEST['interim-login']) || isset($_REQUEST['reauth']) || isset($_REQUEST['customize-login'])) {
            return;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : 'login';
        if (isset($_GET['checkemail'])) {
            $action = 'checkemail';
        }
        $view = '';
        if ($action === 'register') {
            $view = 'inscription';
        } elseif ($action === 'lostpassword' || $action === 'retrievepassword') {
            $view = 'mot-de-passe';
        } elseif ($action === 'checkemail') {
            $view = 'mot-de-passe';
        } elseif ($action !== 'login' && $action !== '') {
            return;
        }

        $redirect = isset($_REQUEST['redirect_to']) ? (string) wp_unslash($_REQUEST['redirect_to']) : '';
        $url = cat_social_auth_page_url($redirect, $view);
        if ($action === 'checkemail') {
            $url = add_query_arg('info', 'email_envoye', $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    /**
     * @param mixed $username
     * @param mixed $error
     */
    public static function redirectFailedLogin($username, $error = null): void
    {
        unset($username, $error);
        if (self::isBackgroundRequest() || !self::isLoginScript() || isset($_REQUEST['interim-login'])) {
            return;
        }

        $redirect = isset($_REQUEST['redirect_to']) ? (string) wp_unslash($_REQUEST['redirect_to']) : '';
        $url = add_query_arg(
            ['erreur' => 'identifiants', 'email' => '1'],
            cat_social_auth_page_url($redirect)
        );
        wp_safe_redirect($url);
        exit;
    }

    /**
     * @param mixed $allow
     * @param mixed $userId
     * @return mixed
     */
    public static function filterPasswordReset($allow, $userId)
    {
        if ($allow !== true) {
            return $allow;
        }

        $userId = (int) $userId;
        if ($userId <= 0) {
            return $allow;
        }
        if (get_user_meta($userId, SocialAuthAccountPlan::META_PASSWORD_UNSET, true) !== '1') {
            return $allow;
        }

        $confirmed = isset($_POST['cat_social_password_setup'])
            && (string) wp_unslash($_POST['cat_social_password_setup']) === '1';
        $nonce = isset($_POST['_wpnonce']) ? (string) wp_unslash($_POST['_wpnonce']) : '';
        if ($confirmed && wp_verify_nonce($nonce, 'cat_auth_lostpassword')) {
            return true;
        }

        return new \WP_Error(
            'cat_social_password',
            cat_social_auth_info_message((new SocialAuthService())->socialInfoCode($userId))
        );
    }

    /**
     * @param mixed $user
     */
    public static function clearSocialPasswordFlag($user): void
    {
        if ($user instanceof \WP_User) {
            delete_user_meta($user->ID, SocialAuthAccountPlan::META_PASSWORD_UNSET);
        }
    }

    private static function isPost(): bool
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST';
    }

    private static function isLoginScript(): bool
    {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return $script === 'wp-login.php';
    }

    private static function isBackgroundRequest(): bool
    {
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return true;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return true;
        }
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        return false;
    }
}
