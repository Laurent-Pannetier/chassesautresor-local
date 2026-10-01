<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAnswerContextService;

if (!function_exists('utilisateur_peut_repondre_manuelle')) {
    function utilisateur_peut_repondre_manuelle(int $userId, int $riddleId): bool {
        return (new RiddleAnswerContextService())->canAnswer($userId, $riddleId);
    }
}

if (!function_exists('calculer_contexte_points')) {
    /** @return array<string,int|string> */
    function calculer_contexte_points(int $userId, int $riddleId): array {
        return (new RiddleAnswerContextService())->points($userId, $riddleId);
    }
}
