<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Parcours Discord et Google : état, échange du code, création ou liaison du compte.
 */
final class SocialAuthService
{
    public const STATE_COOKIE = 'cat_oauth_state';
    public const LINK_COOKIE = 'cat_oauth_link';
    private const STATE_TTL = 600;
    private const MAX_LINK_ATTEMPTS = 5;

    private SocialAuthSettings $settings;

    private SocialAuthEndpoints $endpoints;

    private SocialAuthAccountPlan $plan;

    private SocialAuthIdentity $identity;

    private SocialAuthRedirect $redirects;

    private SocialAuthUsername $usernames;

    private SocialAuthHttpClient $http;

    public function __construct(
        ?SocialAuthSettings $settings = null,
        ?SocialAuthEndpoints $endpoints = null,
        ?SocialAuthAccountPlan $plan = null,
        ?SocialAuthIdentity $identity = null,
        ?SocialAuthRedirect $redirects = null,
        ?SocialAuthUsername $usernames = null,
        ?SocialAuthHttpClient $http = null
    ) {
        $this->settings = $settings ?? new SocialAuthSettings();
        $this->endpoints = $endpoints ?? new SocialAuthEndpoints();
        $this->plan = $plan ?? new SocialAuthAccountPlan();
        $this->identity = $identity ?? new SocialAuthIdentity();
        $this->redirects = $redirects ?? new SocialAuthRedirect();
        $this->usernames = $usernames ?? new SocialAuthUsername();
        $this->http = $http ?? new SocialAuthHttpClient();
    }

    /**
     * @return array<string, string>
     */
    public function storedSettings(): array
    {
        $stored = get_option(SocialAuthSettings::OPTION, []);

        return $this->settings->sanitize(
            is_array($stored) ? $stored : [],
            is_array($stored) ? $stored : []
        );
    }

    public function safeRedirect(string $candidate): string
    {
        return $this->redirects->accept($candidate, home_url('/'));
    }

    /**
     * @return array{ok:bool,url:string,error:string}
     */
    public function begin(string $provider, string $redirect, int $currentUserId): array
    {
        $provider = $this->provider($provider);
        $settings = $this->storedSettings();
        if ($provider === '' || !$this->settings->ready($settings, $provider)) {
            return ['ok' => false, 'url' => '', 'error' => 'provider'];
        }

        $state = bin2hex(random_bytes(32));
        $payload = [
            'state' => $state,
            'provider' => $provider,
            'redirect' => $this->safeRedirect($redirect),
            'user_id' => max(0, $currentUserId),
        ];
        set_transient($this->stateKey($state), $payload, self::STATE_TTL);
        $this->cookie(self::STATE_COOKIE, $state, time() + self::STATE_TTL);

        $url = $this->endpoints->authorizeUrl(
            $provider,
            (string) $settings[$provider . '_client_id'],
            cat_social_auth_callback_url(),
            $state
        );
        if (!$this->endpoints->isProviderAuthorizeUrl($url)) {
            $this->forgetState($state);

            return ['ok' => false, 'url' => '', 'error' => 'provider'];
        }

        return ['ok' => true, 'url' => $url, 'error' => ''];
    }

    /**
     * @return array{ok:bool,redirect:string,error:string,info:string,view:string}
     */
    public function complete(string $code, string $state, string $cookieState): array
    {
        $failure = $this->result(false, 'state');
        if (!$this->isState($state) || !hash_equals($state, $cookieState)) {
            $this->forgetState($state);

            return $failure;
        }

        $stored = get_transient($this->stateKey($state));
        $this->forgetState($state);
        $storedState = is_array($stored) ? (string) ($stored['state'] ?? '') : '';
        $storedProvider = is_array($stored) ? (string) ($stored['provider'] ?? '') : '';
        if ($storedState === '' || $storedProvider === '' || !hash_equals($state, $storedState)) {
            return $failure;
        }

        $provider = $this->provider($storedProvider);
        $redirect = $this->safeRedirect((string) ($stored['redirect'] ?? ''));
        $sessionUserId = (int) ($stored['user_id'] ?? 0);
        if ($provider === '' || !$this->isAuthorizationCode($code)) {
            return $this->result(false, 'provider', $redirect);
        }

        $profile = $this->fetchProfile($provider, $code);
        if ($profile === null) {
            return $this->result(false, 'provider', $redirect);
        }

        $decision = $this->plan->decide(
            $profile['id'],
            $profile['email'],
            $profile['email_verified'],
            $this->userIdByProvider($provider, $profile['id']),
            $profile['email'] !== '' ? $this->userIdByEmail($profile['email']) : null,
            $sessionUserId
        );

        if ($decision['decision'] === SocialAuthAccountPlan::REFUSE) {
            return $this->result(false, $decision['reason'], $redirect);
        }

        if ($decision['decision'] === SocialAuthAccountPlan::LOGIN) {
            $this->loginUser($decision['user_id']);

            return $this->afterLogin($decision['user_id'], $redirect);
        }

        if ($decision['decision'] === SocialAuthAccountPlan::LINK_SESSION) {
            $this->attachProvider($decision['user_id'], $provider, $profile['id']);
            $this->loginUser($decision['user_id']);

            return $this->afterLogin($decision['user_id'], $redirect);
        }

        if ($decision['decision'] === SocialAuthAccountPlan::LINK_PASSWORD) {
            $this->rememberLink($decision['user_id'], $provider, $profile['id'], $redirect);

            return [
                'ok' => true,
                'redirect' => '',
                'error' => '',
                'info' => '',
                'view' => 'relier',
            ];
        }

        $created = $this->createUser($provider, $profile);
        if ($created <= 0) {
            return $this->result(false, 'provider', $redirect);
        }

        $this->loginUser($created);

        return $this->afterLogin($created, $redirect);
    }

    /**
     * @return array{provider:string,valid:bool,redirect:string}
     */
    public function pendingLink(): array
    {
        $token = isset($_COOKIE[self::LINK_COOKIE]) ? (string) $_COOKIE[self::LINK_COOKIE] : '';
        $payload = $this->linkPayload($token);
        if ($payload === null) {
            return ['provider' => '', 'valid' => false, 'redirect' => home_url('/')];
        }

        return [
            'provider' => (string) $payload['provider'],
            'valid' => true,
            'redirect' => $this->safeRedirect((string) $payload['redirect']),
        ];
    }

    /**
     * @return array{ok:bool,redirect:string,error:string,info:string,view:string}
     */
    public function completeLink(string $password): array
    {
        $token = isset($_COOKIE[self::LINK_COOKIE]) ? (string) $_COOKIE[self::LINK_COOKIE] : '';
        $payload = $this->linkPayload($token);
        if ($payload === null) {
            return $this->result(false, 'lien_expire');
        }

        $user = get_user_by('id', (int) $payload['user_id']);
        $redirect = $this->safeRedirect((string) $payload['redirect']);
        if (!$user instanceof \WP_User || $password === '') {
            return $this->result(false, 'lien', $redirect, 'relier');
        }

        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            $payload['attempts'] = (int) $payload['attempts'] + 1;
            if ($payload['attempts'] >= self::MAX_LINK_ATTEMPTS) {
                $this->forgetLink($token);

                return $this->result(false, 'lien_expire', $redirect);
            }
            set_transient($this->linkKey($token), $payload, self::STATE_TTL);

            return $this->result(false, 'lien', $redirect, 'relier');
        }

        $this->attachProvider($user->ID, (string) $payload['provider'], (string) $payload['provider_user_id']);
        $this->forgetLink($token);
        $this->loginUser($user->ID);

        return $this->afterLogin($user->ID, $redirect);
    }

    public function acceptTerms(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        update_user_meta($userId, SocialAuthAccountPlan::META_CGU_ACCEPTED, (string) time());
        delete_user_meta($userId, SocialAuthAccountPlan::META_CGU_PENDING);

        return true;
    }

    /**
     * @return array{ok:bool,redirect:string,error:string,info:string,view:string}
     */
    public function registerWithEmail(string $email, string $displayName, bool $acceptedTerms): array
    {
        if (!$acceptedTerms) {
            return $this->result(false, 'cgu', home_url('/'), 'inscription');
        }
        if (get_option('users_can_register') !== '1') {
            return $this->result(false, 'inscription_fermee', home_url('/'), 'inscription');
        }

        $email = sanitize_email($email);
        if (!is_email($email)) {
            return $this->result(false, 'email_invalide', home_url('/'), 'inscription');
        }
        if (email_exists($email)) {
            return $this->result(false, 'email_existe', home_url('/'), 'inscription');
        }

        $displayName = sanitize_text_field($displayName);
        $local = strstr($email, '@', true);
        $login = $this->usernames->propose(
            'email',
            $email,
            is_string($local) ? $local : '',
            static fn (string $candidate): bool => username_exists($candidate) !== false
        );
        $login = sanitize_user($login, true);
        if ($login === '' || username_exists($login)) {
            return $this->result(false, 'inscription', home_url('/'), 'inscription');
        }

        $userId = wp_insert_user([
            'user_login' => $login,
            'user_email' => $email,
            'user_pass' => wp_generate_password(32, true, true),
            'display_name' => $displayName !== '' ? $displayName : $login,
            'role' => $this->defaultRole(),
        ]);
        if (is_wp_error($userId) || (int) $userId <= 0) {
            return $this->result(false, 'inscription', home_url('/'), 'inscription');
        }

        $userId = (int) $userId;
        update_user_meta($userId, SocialAuthAccountPlan::META_CGU_ACCEPTED, (string) time());
        wp_new_user_notification($userId, null, 'user');

        return [
            'ok' => true,
            'redirect' => '',
            'error' => '',
            'info' => 'inscription_email',
            'view' => 'login',
        ];
    }

    /**
     * @return array{ok:bool,redirect:string,error:string,info:string,view:string}
     */
    public function requestPasswordReset(string $login, bool $confirmed): array
    {
        $login = trim($login);
        $user = is_email($login) ? get_user_by('email', $login) : false;
        if (!$user) {
            $user = get_user_by('login', $login);
        }
        if (!$user instanceof \WP_User) {
            return [
                'ok' => true,
                'redirect' => '',
                'error' => '',
                'info' => 'email_envoye',
                'view' => 'mot-de-passe',
            ];
        }

        $socialOnly = get_user_meta($user->ID, SocialAuthAccountPlan::META_PASSWORD_UNSET, true) === '1';
        if ($this->plan->passwordReset($socialOnly, $confirmed) === 'explain') {
            return [
                'ok' => true,
                'redirect' => '',
                'error' => '',
                'info' => $this->socialInfoCode($user->ID),
                'view' => 'mot-de-passe',
            ];
        }

        $result = retrieve_password($user->user_login);
        if (is_wp_error($result)) {
            return $this->result(false, 'provider', home_url('/'), 'mot-de-passe');
        }

        return [
            'ok' => true,
            'redirect' => '',
            'error' => '',
            'info' => 'email_envoye',
            'view' => 'mot-de-passe',
        ];
    }

    public function socialInfoCode(int $userId): string
    {
        $discord = (string) get_user_meta($userId, SocialAuthAccountPlan::providerMetaKey('discord'), true);
        $google = (string) get_user_meta($userId, SocialAuthAccountPlan::providerMetaKey('google'), true);
        if ($discord !== '' && $google !== '') {
            return 'social_both';
        }
        if ($google !== '') {
            return 'social_google';
        }

        return 'social_discord';
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function createUser(string $provider, array $profile): int
    {
        $login = $this->usernames->propose(
            $provider,
            (string) $profile['id'],
            (string) $profile['display_name'],
            static fn (string $candidate): bool => username_exists($candidate) !== false
        );
        $login = sanitize_user($login, true);
        if ($login === '' || username_exists($login)) {
            $login = sanitize_user($provider . substr(hash('sha256', (string) $profile['id']), 0, 10), true);
        }
        if ($login === '' || username_exists($login)) {
            return 0;
        }

        $display = sanitize_text_field((string) $profile['display_name']);
        $given = sanitize_text_field((string) $profile['given_name']);
        $userId = wp_insert_user([
            'user_login' => $login,
            'user_email' => (string) $profile['email'],
            'user_pass' => wp_generate_password(32, true, true),
            'display_name' => $display !== '' ? $display : $login,
            'first_name' => $given,
            'role' => $this->defaultRole(),
        ]);
        if (is_wp_error($userId) || (int) $userId <= 0) {
            return 0;
        }

        $userId = (int) $userId;
        $this->attachProvider($userId, $provider, (string) $profile['id']);
        update_user_meta($userId, SocialAuthAccountPlan::META_PASSWORD_UNSET, '1');
        update_user_meta($userId, SocialAuthAccountPlan::META_CGU_PENDING, '1');

        return $userId;
    }

    private function attachProvider(int $userId, string $provider, string $providerUserId): void
    {
        $key = SocialAuthAccountPlan::providerMetaKey($provider);
        if ($userId <= 0 || $key === '' || $providerUserId === '') {
            return;
        }

        update_user_meta($userId, $key, $providerUserId);
    }

    private function loginUser(int $userId): void
    {
        $user = get_user_by('id', $userId);
        if (!$user instanceof \WP_User) {
            return;
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);
    }

    /**
     * @return array{ok:bool,redirect:string,error:string,info:string,view:string}
     */
    private function afterLogin(int $userId, string $redirect): array
    {
        $pending = get_user_meta($userId, SocialAuthAccountPlan::META_CGU_PENDING, true) === '1';
        if ($pending) {
            return [
                'ok' => true,
                'redirect' => $redirect,
                'error' => '',
                'info' => '',
                'view' => 'cgu',
            ];
        }

        return [
            'ok' => true,
            'redirect' => $redirect,
            'error' => '',
            'info' => '',
            'view' => '',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchProfile(string $provider, string $code): ?array
    {
        $settings = $this->storedSettings();
        $tokenResponse = $this->http->postForm($this->endpoints->tokenUrl($provider), [
            'client_id' => (string) $settings[$provider . '_client_id'],
            'client_secret' => (string) $settings[$provider . '_client_secret'],
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => cat_social_auth_callback_url(),
        ]);
        if (!$tokenResponse['ok']) {
            return null;
        }

        $token = trim((string) ($tokenResponse['body']['access_token'] ?? ''));
        if ($token === '' || preg_match('/\s/', $token) || strlen($token) > 4096) {
            return null;
        }

        $profileResponse = $this->http->getJson($this->endpoints->identityUrl($provider), $token);
        if (!$profileResponse['ok']) {
            return null;
        }

        $profile = $provider === 'discord'
            ? $this->identity->fromDiscord($profileResponse['body'])
            : $this->identity->fromGoogle($profileResponse['body']);
        if ($profile['id'] === '') {
            return null;
        }

        return $profile;
    }

    private function userIdByProvider(string $provider, string $providerUserId): ?int
    {
        $key = SocialAuthAccountPlan::providerMetaKey($provider);
        if ($key === '' || $providerUserId === '') {
            return null;
        }

        $users = get_users([
            'meta_key' => $key,
            'meta_value' => $providerUserId,
            'number' => 1,
            'fields' => 'ID',
            'count_total' => false,
        ]);
        if (!is_array($users) || !isset($users[0])) {
            return null;
        }

        $userId = (int) $users[0];

        return $userId > 0 ? $userId : null;
    }

    private function userIdByEmail(string $email): ?int
    {
        $user = get_user_by('email', $email);
        if (!$user instanceof \WP_User) {
            return null;
        }

        return (int) $user->ID;
    }

    private function defaultRole(): string
    {
        $role = (string) get_option('default_role');
        if ($role === '' || $role === 'administrator' || !get_role($role)) {
            return 'subscriber';
        }

        return $role;
    }

    private function rememberLink(int $userId, string $provider, string $providerUserId, string $redirect): void
    {
        $token = bin2hex(random_bytes(32));
        set_transient($this->linkKey($token), [
            'token' => $token,
            'user_id' => $userId,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'redirect' => $redirect,
            'attempts' => 0,
        ], self::STATE_TTL);
        $this->cookie(self::LINK_COOKIE, $token, time() + self::STATE_TTL);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function linkPayload(string $token): ?array
    {
        if (!$this->isState($token)) {
            return null;
        }

        $payload = get_transient($this->linkKey($token));
        if (!is_array($payload) || !isset($payload['token']) || !hash_equals($token, (string) $payload['token'])) {
            return null;
        }

        return $payload;
    }

    private function forgetState(string $state): void
    {
        if ($this->isState($state)) {
            delete_transient($this->stateKey($state));
        }
        $this->clearCookie(self::STATE_COOKIE);
    }

    private function forgetLink(string $token): void
    {
        if ($this->isState($token)) {
            delete_transient($this->linkKey($token));
        }
        $this->clearCookie(self::LINK_COOKIE);
    }

    private function stateKey(string $state): string
    {
        return 'cat_oa_' . substr(hash('sha256', $state), 0, 32);
    }

    private function linkKey(string $token): string
    {
        return 'cat_ol_' . substr(hash('sha256', $token), 0, 32);
    }

    private function isState(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function isAuthorizationCode(string $code): bool
    {
        return (bool) preg_match('#^[A-Za-z0-9._~/-]{8,2048}$#', $code);
    }

    private function provider(string $provider): string
    {
        return in_array($provider, ['discord', 'google'], true) ? $provider : '';
    }

    /**
     * @return array{ok:bool,redirect:string,error:string,info:string,view:string}
     */
    private function result(bool $ok, string $error, string $redirect = '', string $view = ''): array
    {
        return [
            'ok' => $ok,
            'redirect' => $redirect !== '' ? $redirect : home_url('/'),
            'error' => $error,
            'info' => '',
            'view' => $view,
        ];
    }

    private function cookie(string $name, string $value, int $expires): void
    {
        $path = defined('COOKIEPATH') && COOKIEPATH !== '' ? COOKIEPATH : '/';
        $domain = defined('COOKIE_DOMAIN') ? (string) COOKIE_DOMAIN : '';
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$name] = $value;
    }

    private function clearCookie(string $name): void
    {
        $this->cookie($name, '', time() - 3600);
        unset($_COOKIE[$name]);
    }
}
