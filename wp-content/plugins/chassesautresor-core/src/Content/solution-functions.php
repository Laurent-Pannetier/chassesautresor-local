<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionCacheUpdater;
use ChassesAuTresor\Core\Content\SolutionCreationRouteHandler;
use ChassesAuTresor\Core\Content\SolutionDisplayService;
use ChassesAuTresor\Core\Content\SolutionPublicationPlanner;
use ChassesAuTresor\Core\Content\SolutionPublicationService;
use ChassesAuTresor\Core\Content\SolutionQueryService;
use ChassesAuTresor\Core\Content\SolutionSaveHandler;
use ChassesAuTresor\Core\Content\SolutionScheduler;

function creer_solution_pour_objet(int $targetId, string $targetType, ?int $userId = null)
{
    return SolutionCreationRouteHandler::create($targetId, $targetType, $userId);
}

function solution_planifier_publication(int $solutionId): void
{
    SolutionPublicationPlanner::plan($solutionId);
}

function solution_rendre_accessible(int $solutionId): void
{
    SolutionPublicationService::makeAccessible($solutionId);
}

function basculer_solutions_programme(): void
{
    SolutionScheduler::run();
}

function planifier_tache_basculer_solutions_programme(): void
{
    SolutionScheduler::schedule();
}

function mettre_a_jour_cache_solution(int $postId): void
{
    SolutionCacheUpdater::update($postId);
}

function solution_acf_save_post(int $postId): void
{
    SolutionSaveHandler::handle($postId);
}

function solution_recuperer_par_objet(int $objectId, string $objectType): ?WP_Post
{
    $queryArgs = (new SolutionQueryService())->getActiveSolutionQueryArgs($objectId, $objectType);
    if ($queryArgs === []) {
        return null;
    }

    $solutions = get_posts($queryArgs);

    return $solutions[0] ?? null;
}

function solution_existe_pour_objet(int $objectId, string $objectType): bool
{
    $queryArgs = (new SolutionQueryService())->getExistingSolutionIdsQueryArgs($objectId, $objectType);
    if ($queryArgs === []) {
        return false;
    }

    return get_posts($queryArgs) !== [];
}

function solution_peut_etre_affichee(int $riddleId): bool
{
    if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
        return false;
    }

    return (new SolutionDisplayService())->canDisplay(
        $riddleId,
        'enigme',
        (int) recuperer_id_chasse_associee($riddleId)
    );
}

function solution_chasse_peut_etre_affichee(int $huntId): bool
{
    return (new SolutionDisplayService())->canDisplay($huntId, 'chasse', $huntId);
}
