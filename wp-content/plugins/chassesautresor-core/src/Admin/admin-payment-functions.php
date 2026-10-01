<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Admin\AdminPaymentRenderer;
use ChassesAuTresor\Core\Relationships\OrganizerRepository;

/** Render the administrator payment table for historical views. */
function render_tableau_paiements_admin(array $requests): string
{
    global $wpdb;

    return (new AdminPaymentRenderer(new OrganizerRepository($wpdb)))->table($requests);
}
