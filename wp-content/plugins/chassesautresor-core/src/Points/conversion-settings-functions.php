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
