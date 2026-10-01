<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\UserProfileCompletionService;

if (!function_exists('cat_is_user_profile_complete')) {
    /** @return array{complete:bool,missing:array<int,string>} */
    function cat_is_user_profile_complete(int $userId): array {
        return (new UserProfileCompletionService())->evaluate($userId);
    }
}

if (!function_exists('cat_get_missing_profile_fields_message')) {
    /** @param array<int,string> $missingFields */
    function cat_get_missing_profile_fields_message(array $missingFields): string {
        return (new UserProfileCompletionService())->missingFieldsMessage($missingFields);
    }
}
