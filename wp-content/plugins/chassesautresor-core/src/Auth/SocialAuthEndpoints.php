<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Adresses OAuth fixes. Le secret client n'entre jamais dans l'URL d'autorisation.
 */
final class SocialAuthEndpoints
{
    public function authorizeUrl(string $provider, string $clientId, string $redirectUri, string $state): string
    {
        if ($provider === 'discord') {
            return 'https://discord.com/api/oauth2/authorize?' . $this->query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'identify email',
                'state' => $state,
            ]);
        }

        if ($provider === 'google') {
            return 'https://accounts.google.com/o/oauth2/v2/auth?' . $this->query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state,
                'prompt' => 'select_account',
            ]);
        }

        return '';
    }

    public function tokenUrl(string $provider): string
    {
        if ($provider === 'discord') {
            return 'https://discord.com/api/oauth2/token';
        }
        if ($provider === 'google') {
            return 'https://oauth2.googleapis.com/token';
        }

        return '';
    }

    public function identityUrl(string $provider): string
    {
        if ($provider === 'discord') {
            return 'https://discord.com/api/users/@me';
        }
        if ($provider === 'google') {
            return 'https://openidconnect.googleapis.com/v1/userinfo';
        }

        return '';
    }

    public function isProviderAuthorizeUrl(string $url): bool
    {
        return str_starts_with($url, 'https://discord.com/api/oauth2/authorize?')
            || str_starts_with($url, 'https://accounts.google.com/o/oauth2/v2/auth?');
    }

    /**
     * @param array<string, string> $params
     */
    private function query(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
