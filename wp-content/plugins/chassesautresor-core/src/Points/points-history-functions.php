<?php

declare(strict_types=1);

/** Format a points history reason and link its related content. */
function format_points_history_reason(array $operation): string
{
    return (new ChassesAuTresor\Core\Points\PointsHistoryRenderer())->formatReason($operation);
}
