<?php
/**
 * Organizer account home shell.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

myaccount_render_dashboard_section(
    __('Pilotage de la chasse', 'chassesautresor-com'),
    __('Éditez vos entités et pilotez le cycle de vie depuis cet accueil.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Accès rapide édition', 'chassesautresor-com'),
        __('Les raccourcis vers organisateur, chasse et énigmes arriveront ici.', 'chassesautresor-com')
    );
    myaccount_render_dashboard_placeholder(
        __('Éditer / Activer', 'chassesautresor-com'),
        __('Le switch de cycle de vie remplacera l’ancien parcours de validation sur les fiches.', 'chassesautresor-com')
    );
    if (function_exists('cat_is_demo_mode') && cat_is_demo_mode()) {
        myaccount_render_dashboard_placeholder(
            __('Reset stats', 'chassesautresor-com'),
            __('Disponible ici en mode démo uniquement.', 'chassesautresor-com')
        );
    }
    ?>
</div>

<?php
myaccount_render_dashboard_section(
    __('Statistiques', 'chassesautresor-com'),
    __('Vue détaillée de la participation à votre chasse.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Statistiques de la chasse', 'chassesautresor-com'),
        __('Les indicateurs détaillés de votre chasse seront affichés ici.', 'chassesautresor-com')
    );
    ?>
</div>
