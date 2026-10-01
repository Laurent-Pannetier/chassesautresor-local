<?php
require_once __DIR__ . '/../constants.php';

// ==================================================
// 💡 GESTION DES SOLUTIONS (création, redirection, AJAX)
// ==================================================

/**
 * Redirige l’affichage d’une solution vers sa chasse ou son énigme liée.
 *
 * @return void
 */
function rediriger_si_affichage_solution(): void
{
    ChassesAuTresor\Core\Content\SolutionRedirectHandler::redirectIfViewingSolution();
}

/**
 * Crée une solution liée à une chasse ou une énigme.
 *
 * @param int      $objet_id   ID de la chasse ou de l’énigme.
 * @param string   $objet_type Type de cible ('chasse' ou 'enigme').
 * @param int|null $user_id    ID utilisateur (null = courant).
 * @return int|WP_Error
 */
function creer_solution_pour_objet(int $objet_id, string $objet_type, ?int $user_id = null)
{
    return ChassesAuTresor\Core\Content\SolutionCreationRouteHandler::create(
        $objet_id,
        $objet_type,
        $user_id
    );
}

/**
 * Enregistre l’URL personnalisée /creer-solution/
 *
 * @return void
 */
function register_endpoint_creer_solution(): void
{
    ChassesAuTresor\Core\Content\SolutionRouteRegistrar::register();
}

/**
 * S'assure que les règles de réécriture prennent en compte /creer-solution/.
 *
 * @return void
 */
function flush_rewrite_rules_creer_solution(): void
{
    ChassesAuTresor\Core\Content\SolutionRouteRegistrar::flush();
}



/**
 * Connects the core solution controllers to the theme permission policy.
 */
/**
 * Renders solution rows requested by the core controller.
 *
 * @param object[] $solutions
 */
function rendre_table_solutions(
    string $html,
    array $solutions,
    int $page,
    int $pages,
    string $targetType,
    int $targetId
): string {
    ob_start();
    get_template_part('template-parts/common/solutions-table', null, [
        'solutions' => $solutions,
        'page' => $page,
        'pages' => $pages,
        'objet_type' => $targetType,
        'objet_id' => $targetId,
    ]);

    return (string) ob_get_clean();
}
add_filter('chassesautresor_render_solutions_table', 'rendre_table_solutions', 10, 7);
