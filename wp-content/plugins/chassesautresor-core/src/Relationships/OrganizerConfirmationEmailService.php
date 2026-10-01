<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

use Closure;

final class OrganizerConfirmationEmailService
{
    private Closure $sendEmail;

    public function __construct(?callable $sendEmail = null)
    {
        $this->sendEmail = Closure::fromCallable($sendEmail ?? 'wp_mail');
    }

    public function send(int $userId, string $token): bool
    {
        $user = get_userdata($userId);
        if (!$user || !is_email($user->user_email) || $token === '') {
            return false;
        }

        $confirmationUrl = add_query_arg(
            ['user' => $userId, 'token' => $token],
            site_url('/confirmation-organisateur/')
        );
        $subject = __('[Chasses au Trésor] Confirmez votre inscription organisateur', 'chassesautresor-com');
        $body = sprintf(
            /* translators: 1: user display name, 2: confirmation URL. */
            __(
                "Bonjour %1\$s,\n\nConfirmez votre inscription : %2\$s\n\nCe lien est valable pendant 2 jours.",
                'chassesautresor-com'
            ),
            $user->display_name,
            $confirmationUrl
        );

        return (bool) ($this->sendEmail)(
            $user->user_email,
            $subject,
            $body,
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }
}
