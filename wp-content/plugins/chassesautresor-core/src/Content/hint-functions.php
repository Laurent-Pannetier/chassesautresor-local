<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCreationRouteHandler;

function creer_indice_pour_objet(int $targetId, string $targetType, ?int $userId = null)
{
    return HintCreationRouteHandler::create($targetId, $targetType, $userId);
}
