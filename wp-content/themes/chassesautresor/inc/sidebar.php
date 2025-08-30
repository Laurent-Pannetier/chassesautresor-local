<?php
defined('ABSPATH') || exit;

/**
 * Sidebar component rendering functions.
 */

if (!function_exists('sidebar_get_section_html')) {
    /**
     * Retrieve HTML for a sidebar section.
     *
     * @param string $section Section identifier.
     * @param array  $args    Data passed to the template.
     *
     * @return string
     */
    function sidebar_get_section_html(string $section, array $args): string
    {
        $args['section'] = $section;
        ob_start();
        get_template_part('template-parts/common/sidebar-section', null, $args);
        return (string) ob_get_clean();
    }
}

if (!function_exists('render_sidebar')) {
    /**
     * Render the sidebar component and return its sections.
     *
     * @param string   $context              Rendering context ('enigme' or 'chasse').
     * @param int      $enigme_id            Enigma identifier.
     * @param bool     $edition_active       Whether the edition mode is active.
     * @param int|null $chasse_id            Associated hunt ID.
     * @param array    $menu_items           Menu items to display.
     * @param bool     $peut_ajouter_enigme  Whether a new enigma can be added.
     * @param int      $total_enigmes        Total number of enigmas.
     * @param bool     $has_incomplete_enigme Whether there is an incomplete enigma.
     *
     * @return array{navigation:string,stats:string}
     */
    function render_sidebar(
        string $context,
        int $enigme_id,
        bool $edition_active,
        ?int $chasse_id,
        array $menu_items,
        bool $peut_ajouter_enigme = false,
        int $total_enigmes = 0,
        bool $has_incomplete_enigme = false
    ): array {
        if ($context === 'enigme') {
            $mode    = get_field('enigme_mode_validation', $enigme_id);
            $user_id = get_current_user_id();

            $stats_html = enigme_sidebar_progression_html($chasse_id, $user_id)
                . enigme_sidebar_resolution_html($enigme_id);
            $meta_html   = enigme_sidebar_metas_html($enigme_id);
            $winners_html = $mode === 'aucune'
                ? ''
                : enigme_sidebar_gagnants_html($enigme_id, $user_id);
        } else {
            $stats_html   = '';
            $meta_html    = '';
            $winners_html = '';
        }

        $ajout_html = '';
        if (
            $chasse_id
            && $peut_ajouter_enigme
            && $total_enigmes > 0
            && !$has_incomplete_enigme
        ) {
            ob_start();
            get_template_part('template-parts/enigme/chasse-partial-ajout-enigme', null, [
                'has_enigmes' => true,
                'chasse_id'   => $chasse_id,
                'use_button'  => true,
            ]);
            $ajout_html = ob_get_clean();
        }

        $max_visible   = function_exists('apply_filters')
            ? (int) apply_filters('enigme_menu_max_visible', 10)
            : 10;
        $visible_items = array_slice($menu_items, 0, $max_visible);
        $hidden_items  = array_slice($menu_items, $max_visible);

        $navigation_html = sidebar_get_section_html('navigation', [
            'visible_items'  => $visible_items,
            'hidden_items'   => $hidden_items,
            'edition_active' => $edition_active,
            'chasse_id'      => $chasse_id,
            'ajout_html'     => $ajout_html,
            'context'        => $context,
        ]);
        if ($navigation_html === '') {
            $navigation_html = '<section class="enigme-navigation"><h3>'
                . esc_html__('Énigmes', 'chassesautresor-com')
                . '</h3><ul class="enigme-menu"></ul></section>';
        }

        $stats_section_html = sidebar_get_section_html('stats', [
            'meta_html'    => $meta_html,
            'stats_html'   => $stats_html,
            'winners_html' => $winners_html,
            'enigme_id'    => $enigme_id,
            'context'      => $context,
        ]);

        echo '<aside class="menu-lateral" data-context="' . esc_attr($context) . '">';

        echo '<div class="menu-lateral__header">';
        if ($chasse_id) {
            $url_chasse  = get_permalink($chasse_id);
            $titre       = get_the_title($chasse_id);
            $retour_icon = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
            echo '<a class="menu-lateral__back" href="' . esc_url($url_chasse) . '"><span class="screen-reader-text">' . esc_html__("Retour", "chassesautresor-com") . '</span>' . $retour_icon . '</a>';
            echo '<h2 class="menu-lateral__title"><a href="' . esc_url($url_chasse) . '">' . esc_html($titre) . '</a></h2>';
        }
        echo '</div>';

        echo '<div class="menu-lateral__content">' . $navigation_html . '</div>';
        echo '<div class="menu-lateral__accordeons">';
        echo '<div class="accordeon-bloc">';
        echo '<div class="accordeon-contenu accordeon-ferme">' . $stats_section_html . '</div>';
        echo '<button class="accordeon-toggle" type="button" aria-expanded="false">'
            . '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i>'
            . '<span class="screen-reader-text">'
            . esc_html__('Afficher les statistiques', 'chassesautresor-com')
            . '</span></button>';
        echo '</div>';
        echo '</div>';
        echo '</aside>';

        return [
            'navigation' => $navigation_html,
            'stats'      => $stats_section_html,
        ];
    }
}

