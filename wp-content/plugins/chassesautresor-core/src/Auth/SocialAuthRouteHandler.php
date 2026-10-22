<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Route /connexion/ : démarrage OAuth, retour du fournisseur, formulaires e-mail.
 */
final class SocialAuthRouteHandler
{
    private const FLUSHED_OPTION = 'cat_connexion_rewrite_v1';

    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'registerRewrites']);
        $addAction('init', [self::class, 'maybeFlush'], 20);
        $addAction('template_redirect', [self::class, 'handle'], 0);
    }

    public static function registerRewrites(): void
    {
        add_rewrite_rule('^connexion/?$', 'index.php?cat_connexion=1', 'top');
        add_rewrite_tag('%cat_connexion%', '1');
    }

    public static function flush(): void
    {
        self::registerRewrites();
        flush_rewrite_rules();
        update_option(self::FLUSHED_OPTION, 1);
    }

    public static function maybeFlush(): void
    {
        if (!get_option(self::FLUSHED_OPTION)) {
            self::flush();
        }
    }

    public static function handle(): void
    {
        self::captureRequest();
        if (!cat_social_auth_is_request()) {
            return;
        }

        if (function_exists('status_header')) {
            status_header(200);
        }
        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->is_404 = false;
        }
        nocache_headers();
        self::setTestCookie();

        $service = new SocialAuthService();
        if (self::isPost()) {
            self::handlePost($service);
            return;
        }

        $code = isset($_GET['code']) ? (string) wp_unslash($_GET['code']) : '';
        $state = isset($_GET['state']) ? (string) wp_unslash($_GET['state']) : '';
        if ($code !== '' && $state !== '') {
            $cookieName = SocialAuthService::STATE_COOKIE;
            $cookie = isset($_COOKIE[$cookieName]) ? (string) $_COOKIE[$cookieName] : '';
            self::finish($service->complete($code, $state, $cookie));
        }

        $action = isset($_GET['cat_oauth']) ? sanitize_key((string) wp_unslash($_GET['cat_oauth'])) : '';
        if ($action === 'start') {
            return;
        }

        self::redirectLoggedInVisitor($service);
    }

    private static function handlePost(SocialAuthService $service): void
    {
        $action = isset($_POST['cat_auth_action']) ? sanitize_key((string) wp_unslash($_POST['cat_auth_action'])) : '';
        $redirect = self::postedRedirect();
        if ($action === 'start') {
            $view = self::postedView();
            self::guardNonce('cat_auth_start', $redirect, $view);
            $provider = isset($_POST['provider']) ? sanitize_key((string) wp_unslash($_POST['provider'])) : '';
            $begun = $service->begin($provider, $redirect, (int) get_current_user_id());
            if (!$begun['ok']) {
                $code = (string) ($begun['error'] ?? '');
                self::redirectToScreen($redirect, $view, $code !== '' ? $code : 'provider');
            }
            $endpoints = new SocialAuthEndpoints();
            if (!$endpoints->isProviderAuthorizeUrl($begun['url'])) {
                self::redirectToScreen($redirect, $view, 'provider');
            }
            wp_redirect($begun['url']);
            exit;
        }

        if ($action === 'link') {
            self::guardNonce('cat_auth_link', $redirect, 'relier');
            $password = isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '';
            self::finish($service->completeLink($password));
        }

        if ($action === 'cgu') {
            self::guardNonce('cat_auth_cgu', $redirect, 'cgu');
            $accepted = isset($_POST['cgu']) && (string) wp_unslash($_POST['cgu']) === '1';
            if (!$accepted || !$service->acceptTerms((int) get_current_user_id())) {
                self::redirectToScreen($redirect, 'cgu', 'cgu');
            }
            wp_safe_redirect($service->safeRedirect($redirect));
            exit;
        }

        if ($action === 'register') {
            self::guardNonce('cat_auth_register', $redirect, 'inscription');
            $email = isset($_POST['user_email']) ? (string) wp_unslash($_POST['user_email']) : '';
            $name = isset($_POST['display_name']) ? (string) wp_unslash($_POST['display_name']) : '';
            $accepted = isset($_POST['cgu']) && (string) wp_unslash($_POST['cgu']) === '1';
            self::finish($service->registerWithEmail($email, $name, $accepted));
        }

        if ($action === 'lostpassword') {
            self::guardNonce('cat_auth_lostpassword', $redirect, 'mot-de-passe');
            $login = isset($_POST['user_login']) ? (string) wp_unslash($_POST['user_login']) : '';
            $confirmed = isset($_POST['cat_social_password_setup'])
                && (string) wp_unslash($_POST['cat_social_password_setup']) === '1';
            self::finish($service->requestPasswordReset($login, $confirmed));
        }
    }

    /**
     * @param array{ok:bool,redirect:string,error:string,info:string,view:string} $result
     */
    private static function finish(array $result): void
    {
        if ($result['ok'] && $result['view'] === '' && $result['error'] === '') {
            wp_safe_redirect($result['redirect'] !== '' ? $result['redirect'] : home_url('/'));
            exit;
        }

        $view = $result['view'];
        if ($result['ok'] && $view === 'relier') {
            $view = 'relier';
        }
        self::redirectToScreen(
            (string) ($result['redirect'] ?? ''),
            $view,
            (string) ($result['error'] ?? ''),
            (string) ($result['info'] ?? '')
        );
    }

    private static function redirectLoggedInVisitor(SocialAuthService $service): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $view = self::requestedView();
        $preview = current_user_can('manage_options') && isset($_GET['apercu']);
        $pending = get_user_meta(get_current_user_id(), SocialAuthAccountPlan::META_CGU_PENDING, true) === '1';
        $redirect = self::requestedRedirect($service);
        if ($preview && $view !== 'cgu') {
            return;
        }
        if ($pending && $view !== 'cgu') {
            self::redirectToScreen($redirect, 'cgu');
        }
        if (!$pending) {
            wp_safe_redirect($redirect);
            exit;
        }
    }

    private static function redirectToScreen(
        string $redirect,
        string $view = '',
        string $error = '',
        string $info = ''
    ): void
    {
        $url = cat_social_auth_page_url($redirect, $view);
        $args = [];
        if ($error !== '') {
            $args['erreur'] = $error;
        }
        if ($info !== '') {
            $args['info'] = $info;
        }
        if ($error === 'identifiants') {
            $args['email'] = '1';
        }
        if ($args !== []) {
            $url = add_query_arg($args, $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    private static function guardNonce(string $action, string $redirect, string $view = ''): void
    {
        $nonce = isset($_POST['_wpnonce']) ? (string) wp_unslash($_POST['_wpnonce']) : '';
        if (!wp_verify_nonce($nonce, $action)) {
            self::redirectToScreen($redirect, $view, 'state');
        }
    }

    private static function captureRequest(): void
    {
        if ((string) get_query_var('cat_connexion') === '1') {
            return;
        }

        $request = '';
        if (isset($GLOBALS['wp']) && is_object($GLOBALS['wp']) && isset($GLOBALS['wp']->request)) {
            $request = trim((string) $GLOBALS['wp']->request, '/');
        }
        if ($request === 'connexion') {
            set_query_var('cat_connexion', '1');
        }
    }

    private static function setTestCookie(): void
    {
        $name = defined('TEST_COOKIE') ? TEST_COOKIE : 'wordpress_test_cookie';
        $secure = is_ssl();
        $path = defined('COOKIEPATH') && COOKIEPATH !== '' ? COOKIEPATH : '/';
        $domain = defined('COOKIE_DOMAIN') ? (string) COOKIE_DOMAIN : '';
        setcookie($name, 'WP Cookie check', 0, $path, $domain, $secure, true);
        if (defined('SITECOOKIEPATH') && SITECOOKIEPATH !== $path) {
            setcookie($name, 'WP Cookie check', 0, SITECOOKIEPATH, $domain, $secure, true);
        }
    }

    private static function isPost(): bool
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST';
    }

    private static function postedRedirect(): string
    {
        $redirect = isset($_POST['redirect_to']) ? (string) wp_unslash($_POST['redirect_to']) : '';

        return (new SocialAuthService())->safeRedirect($redirect);
    }

    private static function postedView(): string
    {
        $view = isset($_POST['cat_auth_view']) ? sanitize_key((string) wp_unslash($_POST['cat_auth_view'])) : '';

        return in_array($view, ['login', 'inscription'], true) ? $view : '';
    }

    private static function requestedRedirect(SocialAuthService $service): string
    {
        $redirect = isset($_GET['redirect_to']) ? (string) wp_unslash($_GET['redirect_to']) : '';

        return $service->safeRedirect($redirect);
    }

    private static function requestedView(): string
    {
        $view = isset($_GET['vue']) ? sanitize_key((string) wp_unslash($_GET['vue'])) : '';
        $allowed = ['login', 'inscription', 'mot-de-passe', 'relier', 'cgu'];

        return in_array($view, $allowed, true) ? $view : 'login';
    }
}
