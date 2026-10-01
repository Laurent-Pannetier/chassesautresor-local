<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntValidationAccessResolver;
use ChassesAuTresor\Core\Messages\HuntCorrectionMessageService;

function peut_valider_chasse(int $chasseId, int $userId): bool
{
    return (new HuntValidationAccessResolver())->canRequest($chasseId, $userId);
}

function myaccount_clear_correction_message(int $chasseId): void
{
    (new HuntCorrectionMessageService())->clear($chasseId);
}
