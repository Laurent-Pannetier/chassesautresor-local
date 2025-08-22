<?php
/**
 * Rendu d'une section du panneau/aside d'énigme.
 *
 * @param array $args {
 *     @type string $section       Section à afficher : 'navigation' ou 'stats'.
 *     @type array  $menu_items    Éléments de menu.
 *     @type bool   $edition_active Mode édition actif.
 *     @type int    $chasse_id     Identifiant de la chasse liée.
 *     @type string $ajout_html    HTML du lien d'ajout d'énigme.
 *     @type string $meta_html     Métadonnées de l'énigme.
 *     @type string $stats_html    Statistiques d'engagement/résolution.
 *     @type string $winners_html  Liste des gagnants.
 *     @type int    $enigme_id     Identifiant de l'énigme.
 * }
 */

if (!isset($args['section'])) {
    return;
}

$section = $args['section'];

if ($section === 'navigation') {
    if (!empty($args['chasse_id'])) {
        $logo = get_the_post_thumbnail($args['chasse_id'], 'thumbnail');
        if ($logo) {
            echo '<div class="enigme-chasse-logo">' . $logo . '</div>';
        }
    }
    $data_chasse = $args['chasse_id'] ? ' data-chasse-id="' . intval($args['chasse_id']) . '"' : '';
    $menu_class  = 'enigme-menu';
    if (!empty($args['edition_active'])) {
        $menu_class .= ' enigme-menu--editable';
    }
    echo '<section class="enigme-navigation"' . $data_chasse . '>';
    echo '<h3>' . esc_html__('Énigmes', 'chassesautresor-com') . '</h3>';
    if (!empty($args['ajout_html'])) {
        echo $args['ajout_html'];
    }
    if (empty($args['menu_items'])) {
        echo '<p class="enigme-navigation__empty">' . esc_html__('Aucune énigme disponible', 'chassesautresor-com') . '</p>';
    } else {
        echo '<ul class="' . esc_attr($menu_class) . '">' . implode('', $args['menu_items']) . '</ul>';
    }
    echo '</section>';
    return;
}

if ($section === 'stats') {
    echo '<section class="enigme-statistiques">';
    echo '<h3>' . esc_html__('Statistiques', 'chassesautresor-com') . '</h3>';
    echo $args['meta_html'] ?? '';
    echo $args['stats_html'] ?? '';
    echo '</section>';
    if (!empty($args['winners_html'])) {
        echo '<section class="enigme-gagnants" data-enigme-id="' . intval($args['enigme_id'] ?? 0) . '">';
        echo $args['winners_html'];
        echo '</section>';
    }
}
