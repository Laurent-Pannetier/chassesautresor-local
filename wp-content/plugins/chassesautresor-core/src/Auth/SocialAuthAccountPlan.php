<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Décide quoi faire d'une identité Discord ou Google, sans effet de bord.
 */
final class SocialAuthAccountPlan
{
    public const LOGIN = 'login';
    public const LINK_PASSWORD = 'link_password';
    public const LINK_SESSION = 'link_session';
    public const CREATE = 'create';
    public const REFUSE = 'refuse';

    public const META_PASSWORD_UNSET = 'cat_social_password_unset';
    public const META_CGU_PENDING = 'cat_cgu_pending';
    public const META_CGU_ACCEPTED = 'cat_cgu_accepted';

    public static function providerMetaKey(string $provider): string
    {
        if (!in_array($provider, ['discord', 'google'], true)) {
            return '';
        }

        return 'cat_oauth_' . $provider;
    }

    /**
     * @return array{decision:string,user_id:int,reason:string}
     */
    public function decide(
        string $providerUserId,
        string $email,
        bool $emailVerified,
        ?int $userIdByProvider,
        ?int $userIdByEmail,
        int $currentUserId
    ): array {
        if ($providerUserId === '') {
            return $this->refuse('provider_id');
        }

        if ($userIdByProvider !== null && $userIdByProvider > 0) {
            if ($currentUserId > 0 && $currentUserId !== $userIdByProvider) {
                return $this->refuse('session_conflict');
            }

            return [
                'decision' => self::LOGIN,
                'user_id' => $userIdByProvider,
                'reason' => '',
            ];
        }

        if ($currentUserId > 0 && ($userIdByEmail === null || $userIdByEmail === $currentUserId)) {
            return [
                'decision' => self::LINK_SESSION,
                'user_id' => $currentUserId,
                'reason' => '',
            ];
        }

        if ($currentUserId > 0) {
            return $this->refuse('session_conflict');
        }

        if ($userIdByEmail !== null && $userIdByEmail > 0) {
            if ($email === '' || !$emailVerified) {
                return $this->refuse('email_unverified');
            }

            return [
                'decision' => self::LINK_PASSWORD,
                'user_id' => $userIdByEmail,
                'reason' => '',
            ];
        }

        if ($email === '') {
            return $this->refuse('email_missing');
        }

        if (!$emailVerified) {
            return $this->refuse('email_unverified');
        }

        return [
            'decision' => self::CREATE,
            'user_id' => 0,
            'reason' => '',
        ];
    }

    /**
     * Un compte uniquement social n'envoie un e-mail de mot de passe que sur confirmation.
     */
    public function passwordReset(bool $socialOnly, bool $confirmed): string
    {
        if ($socialOnly && !$confirmed) {
            return 'explain';
        }

        return 'send';
    }

    /**
     * @return array{decision:string,user_id:int,reason:string}
     */
    private function refuse(string $reason): array
    {
        return [
            'decision' => self::REFUSE,
            'user_id' => 0,
            'reason' => $reason,
        ];
    }
}
