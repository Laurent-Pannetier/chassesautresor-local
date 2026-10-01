<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\ConversionSettingsService;

/** Return the current point conversion rate for legacy views. */
function get_taux_conversion_actuel(): float {
    return (new ConversionSettingsService())->getRate();
}

/** Update the point conversion rate for legacy views. */
function update_taux_conversion($nouveau_taux): void {
    (new ConversionSettingsService())->updateRate((float) $nouveau_taux);
}

/** Return the minimum balance required to request a points conversion. */
function get_points_conversion_min(): int {
    return (int) apply_filters('points_conversion_min', 500);
}
