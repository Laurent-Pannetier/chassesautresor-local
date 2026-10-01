<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionCacheUpdater;
use ChassesAuTresor\Core\Content\SolutionPublicationPlanner;
use ChassesAuTresor\Core\Content\SolutionPublicationService;
use ChassesAuTresor\Core\Content\SolutionSaveHandler;
use ChassesAuTresor\Core\Content\SolutionScheduler;

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
