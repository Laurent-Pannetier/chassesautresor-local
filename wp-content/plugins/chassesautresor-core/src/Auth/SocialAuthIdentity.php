<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Normalise les profils renvoyés par Discord et Google.
 */
final class SocialAuthIdentity
{
    /**
     * @param array<string, mixed> $payload
     * @return array{
     *     provider:string,
     *     id:string,
     *     email:string,
     *     email_verified:bool,
     *     display_name:string,
     *     given_name:string
     * }
     */
    public function fromDiscord(array $payload): array
    {
        $display = trim((string) ($payload['global_name'] ?? ''));
        if ($display === '') {
            $display = trim((string) ($payload['username'] ?? ''));
        }

        return $this->normalize(
            'discord',
            trim((string) ($payload['id'] ?? '')),
            (string) ($payload['email'] ?? ''),
            !empty($payload['verified']),
            $display,
            ''
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{
     *     provider:string,
     *     id:string,
     *     email:string,
     *     email_verified:bool,
     *     display_name:string,
     *     given_name:string
     * }
     */
    public function fromGoogle(array $payload): array
    {
        $verified = $payload['email_verified'] ?? false;

        return $this->normalize(
            'google',
            trim((string) ($payload['sub'] ?? '')),
            (string) ($payload['email'] ?? ''),
            $verified === true || $verified === 'true' || $verified === 1 || $verified === '1',
            trim((string) ($payload['name'] ?? '')),
            trim((string) ($payload['given_name'] ?? ''))
        );
    }

    /**
     * @return array{
     *     provider:string,
     *     id:string,
     *     email:string,
     *     email_verified:bool,
     *     display_name:string,
     *     given_name:string
     * }
     */
    private function normalize(
        string $provider,
        string $id,
        string $email,
        bool $verified,
        string $displayName,
        string $givenName
    ): array {
        $email = strtolower(trim($email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $email = '';
            $verified = false;
        }
        if ($email === '') {
            $verified = false;
        }

        return [
            'provider' => $provider,
            'id' => $id,
            'email' => $email,
            'email_verified' => $verified,
            'display_name' => $displayName,
            'given_name' => $givenName,
        ];
    }
}
