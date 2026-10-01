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
