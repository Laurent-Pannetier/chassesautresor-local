<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Identifiants Discord et Google. Un secret vide conserve la valeur déjà enregistrée.
 */
final class SocialAuthSettings
{
    public const OPTION = 'chassesautresor_social_auth';

    /**
     * @return array{
     *     discord_client_id:string,
     *     discord_client_secret:string,
     *     google_client_id:string,
     *     google_client_secret:string
     * }
     */
    public function defaults(): array
    {
        return [
            'discord_client_id' => '',
            'discord_client_secret' => '',
            'google_client_id' => '',
            'google_client_secret' => '',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $previous
     * @return array{
     *     discord_client_id:string,
     *     discord_client_secret:string,
     *     google_client_id:string,
     *     google_client_secret:string
     * }
     */
    public function sanitize(array $input, array $previous): array
    {
        $clean = $this->defaults();
        $clean['discord_client_id'] = $this->clientId((string) ($input['discord_client_id'] ?? ''));
        $clean['google_client_id'] = $this->clientId((string) ($input['google_client_id'] ?? ''));
        $clean['discord_client_secret'] = $this->secret(
            (string) ($input['discord_client_secret'] ?? ''),
            (string) ($previous['discord_client_secret'] ?? '')
        );
        $clean['google_client_secret'] = $this->secret(
            (string) ($input['google_client_secret'] ?? ''),
            (string) ($previous['google_client_secret'] ?? '')
        );

        return $clean;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function ready(array $settings, string $provider): bool
    {
        if (!in_array($provider, ['discord', 'google'], true)) {
            return false;
        }

        $id = trim((string) ($settings[$provider . '_client_id'] ?? ''));
        $secret = trim((string) ($settings[$provider . '_client_secret'] ?? ''));

        return $id !== '' && $secret !== '';
    }

    private function clientId(string $value): string
    {
        $value = trim($value);
        $filtered = preg_replace('/[^A-Za-z0-9._-]/', '', $value);

        return substr(is_string($filtered) ? $filtered : '', 0, 200);
    }

    private function secret(string $incoming, string $previous): string
    {
        $incoming = trim($incoming);
        if ($incoming === '') {
            return $this->cleanSecret($previous);
        }

        $clean = $this->cleanSecret($incoming);

        return $clean !== '' ? $clean : $this->cleanSecret($previous);
    }

    private function cleanSecret(string $value): string
    {
        $filtered = preg_replace('/[^A-Za-z0-9._~+\/=-]/', '', trim($value));

        return substr(is_string($filtered) ? $filtered : '', 0, 300);
    }
}
