<?php

declare(strict_types=1);

/** Render attempt rows for historical theme views. */
function ca_render_tentatives_rows(array $attempts, int $total, string $message): string
{
    return (new ChassesAuTresor\Core\Progress\UserAttemptsRenderer())->rows([
        'tentatives' => $attempts,
        'filtered_total' => $total,
        'no_results_message' => $message,
    ]);
}


if (!function_exists('ca_ajax_fetch_tentatives')) {
    function ca_ajax_fetch_tentatives(): void {
        ChassesAuTresor\Core\Progress\UserAttemptsAjaxHandler::handle();
    }
}
