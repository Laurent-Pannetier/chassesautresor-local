<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountMessageService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

function cat_get_account_message_service(): AccountMessageService
{
    global $wpdb;

    return CoreServiceFactory::accountMessages($wpdb);
}

function myaccount_add_persistent_message(
    int $userId,
    string $key,
    string $message,
    string $type = 'info',
    bool $dismissible = false,
    int $huntScope = 0,
    bool $includeRiddles = false,
    ?string $messageKey = null,
    ?string $locale = null,
    ?int $expires = null
): int {
    global $wpdb;

    $payload = [
        'text' => $message,
        'type' => $type,
        'dismissible' => $dismissible,
    ];
    if ($messageKey !== null) {
        $payload['message_key'] = $messageKey;
    }
    if ($locale !== null) {
        $payload['locale'] = $locale;
    }
    if ($huntScope > 0) {
        $payload['chasse_scope'] = $huntScope;
        $payload['include_enigmes'] = $includeRiddles;
    }

    $messageId = cat_get_account_message_service()->addPersistent(
        $userId,
        $key,
        $payload,
        $locale,
        $expires
    );
    if ($messageId === 0) {
        error_log(
            sprintf(
                'myaccount_add_persistent_message failed for user %d and key %s: %s',
                $userId,
                $key,
                $wpdb->last_error
            )
        );
    }

    return $messageId;
}

function myaccount_remove_persistent_message(int $userId, string $key): void
{
    cat_get_account_message_service()->removePersistent($userId, $key);
}

function myaccount_add_flash_message(
    int $userId,
    string $message,
    string $type = 'info',
    bool $dismissible = false
): void {
    cat_get_account_message_service()->addFlash($userId, [
        'text' => $message,
        'type' => $type,
        'dismissible' => $dismissible,
    ]);
}
