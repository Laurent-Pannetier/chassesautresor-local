<?php
/**
 * Administrator account home shell.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

myaccount_render_dashboard_section(
    __('Pilotage', 'chassesautresor-com'),
    __('Accès rapide et cycle de vie de la chasse.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Accès rapide édition', 'chassesautresor-com'),
        __('Les raccourcis vers les panneaux d’édition des entités arriveront ici.', 'chassesautresor-com')
    );
    myaccount_render_dashboard_placeholder(
        __('Éditer / Activer', 'chassesautresor-com'),
        __('Le switch de cycle de vie (édition ↔ activation) sera centralisé ici.', 'chassesautresor-com')
    );
    if (function_exists('cat_is_demo_mode') && cat_is_demo_mode()) {
        myaccount_render_dashboard_placeholder(
            __('Reset stats', 'chassesautresor-com'),
            __('L’outil de reset des statistiques sera disponible ici en mode démo.', 'chassesautresor-com')
        );
    }
    ?>
</div>

<?php
myaccount_render_dashboard_section(
    __('Statistiques', 'chassesautresor-com'),
    __('Indicateurs par énigme pour suivre la participation.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Statistiques des énigmes', 'chassesautresor-com'),
        __('Participants, étapes, réussites et classements seront affichés ici.', 'chassesautresor-com')
    );
    ?>
</div>

<?php
myaccount_render_dashboard_section(
    __('Outils', 'chassesautresor-com'),
    __('Outils actifs en haut de zone ; le reste reste disponible mais inactif pour l’instant.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Protection globale', 'chassesautresor-com'),
        __('Le contrôle de protection du site sera déplacé ici depuis Outils.', 'chassesautresor-com')
    );
    ?>
</div>
<div class="dashboard-grid dashboard-grid--inactive" aria-disabled="true">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Points', 'chassesautresor-com'),
        __('Inactif pour le moment. Réactivable plus tard via Expérience du site.', 'chassesautresor-com')
    );
    myaccount_render_dashboard_placeholder(
        __('Taux de conversion', 'chassesautresor-com'),
        __('Inactif pour le moment.', 'chassesautresor-com')
    );
    myaccount_render_dashboard_placeholder(
        __('ACF', 'chassesautresor-com'),
        __('Inactif pour le moment.', 'chassesautresor-com')
    );
    ?>
</div>
