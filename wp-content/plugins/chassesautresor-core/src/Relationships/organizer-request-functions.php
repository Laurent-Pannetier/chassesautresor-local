<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\OrganizerConfirmationEmailService;
use ChassesAuTresor\Core\Relationships\OrganizerRequestLifecycleService;

function cat_clear_organisateur_request(int $userId): void
{
    (new OrganizerRequestLifecycleService())->clear($userId);
}

/** @return array{token:?string,expired:bool,expires_at?:int} */
function cat_get_organisateur_request_status(int $userId): array
{
    return (new OrganizerRequestLifecycleService())->getStatus($userId);
}

function envoyer_email_confirmation_organisateur(int $userId, string $token): bool
{
    return (new OrganizerConfirmationEmailService())->send($userId, $token);
}

function lancer_demande_organisateur(int $userId): bool
{
    $email = new OrganizerConfirmationEmailService();

    return (new OrganizerRequestLifecycleService())->start(
        $userId,
        static fn (int $targetUserId, string $token): bool => $email->send($targetUserId, $token)
    );
}

function renvoyer_email_confirmation_organisateur(int $userId): bool
{
    $email = new OrganizerConfirmationEmailService();

    return (new OrganizerRequestLifecycleService())->resend(
        $userId,
        static fn (int $targetUserId, string $token): bool => $email->send($targetUserId, $token)
    );
}
