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


if (!function_exists('sidebar_prepare_chasse_nav')) {
    /**
     * Prepare navigation items for a hunt sidebar.
     *
     * @param int $chasse_id         Hunt identifier.
     * @param int $user_id           Current user identifier.
     * @param int $current_enigme_id Current enigma identifier.
     *
     * @return array{menu_items:array,peut_ajouter_enigme:bool,total_enigmes:int,has_incomplete_enigme:bool,visible_ids:array}
     */
    function sidebar_prepare_chasse_nav(int $chasse_id, int $user_id, int $current_enigme_id = 0): array
    {
        $all_enigmes         = recuperer_enigmes_pour_chasse($chasse_id);
        $submenu_items       = [];
        $total_enigmes       = count($all_enigmes);
        $has_incomplete_enigme = false;

        foreach ($all_enigmes as $post_check) {
            if (!get_field('enigme_cache_complet', $post_check->ID)) {
                $has_incomplete_enigme = true;
                break;
            }
        }

        $peut_ajouter_enigme = function_exists('utilisateur_peut_ajouter_enigme')
            ? utilisateur_peut_ajouter_enigme($chasse_id)
            : false;

        $visible_ids = [];

        foreach ($all_enigmes as $post) {
            $cta = get_cta_enigme($post->ID, $user_id);

            if (in_array($cta['etat_systeme'], ['bloquee_date', 'bloquee_pre_requis'], true)) {
                continue;
            }

            $classes = [];

            if ($cta['etat_systeme'] === 'bloquee_chasse') {
                if (!get_field('enigme_cache_complet', $post->ID)) {
                    $classes[] = 'incomplete';
                } else {
                    $classes[] = 'bloquee';
                }
            } else {
                $statut_user = $cta['statut_utilisateur'];
                if (in_array($statut_user, ['resolue', 'terminee'], true)) {
                    $classes[] = 'succes';
                } elseif ($statut_user === 'soumis') {
                    $classes[] = 'en-attente';
                } elseif (
                    $statut_user === 'non_commencee'
                    && !utilisateur_est_engage_dans_enigme($user_id, $post->ID)
                ) {
                    $classes[] = 'non-engagee';
                }
            }

            if ($post->ID === $current_enigme_id) {
                $classes[] = 'active';
            }

            $title        = esc_html(get_the_title($post->ID));
            $aria_current = $post->ID === $current_enigme_id ? ' aria-current="page"' : '';
            $link         = '<a href="' . esc_url(get_permalink($post->ID)) . '"' . $aria_current . '>' . $title . '</a>';
            $submenu_items[] = sprintf(
                '<li class="%s" data-enigme-id="%d">%s</li>',
                esc_attr(implode(' ', $classes)),
                $post->ID,
                $link
            );
            $visible_ids[] = $post->ID;
        }

        return [
            'menu_items'           => $submenu_items,
            'peut_ajouter_enigme'  => $peut_ajouter_enigme,
            'total_enigmes'        => $total_enigmes,
            'has_incomplete_enigme' => $has_incomplete_enigme,
            'visible_ids'          => $visible_ids,
        ];
    }
}

if (!function_exists('ajax_chasse_recuperer_navigation')) {
    /**
     * AJAX handler to refresh hunt navigation items.
     */
    function ajax_chasse_recuperer_navigation(): void
    {
        $chasse_id = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
            wp_send_json_error('post_invalide', 400);
        }

        $user_id = get_current_user_id();
        $data    = sidebar_prepare_chasse_nav($chasse_id, $user_id);
        wp_send_json_success([
            'html' => implode('', $data['menu_items']),
            'ids'  => $data['visible_ids'],
        ]);
    }
    add_action('wp_ajax_chasse_recuperer_navigation', 'ajax_chasse_recuperer_navigation');
    add_action('wp_ajax_nopriv_chasse_recuperer_navigation', 'ajax_chasse_recuperer_navigation');
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
            $navigation_html = '<nav class="enigme-navigation" aria-label="'
                . esc_attr__('Navigation des énigmes', 'chassesautresor-com')
                . '"><h3>'
                . esc_html__('Énigmes', 'chassesautresor-com')
                . '</h3><ul class="enigme-menu"></ul></nav>';
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
            $url_chasse = get_permalink($chasse_id);
            $titre      = get_the_title($chasse_id);
            $can_edit   = function_exists('utilisateur_peut_voir_panneau')
                ? utilisateur_peut_voir_panneau($chasse_id)
                : false;

            echo '<h2 class="menu-lateral__title"><a href="' . esc_url($url_chasse) . '">' . esc_html($titre) . '</a></h2>';

            if ($can_edit) {
                $edit_url = function_exists('add_query_arg')
                    ? add_query_arg(
                        ['edition' => 'open', 'tab' => 'param'],
                        $url_chasse
                    )
                    : $url_chasse . '?edition=open&tab=param';
                echo '<a class="menu-lateral__edition-toggle enigme-menu__edit" href="'
                    . esc_url($edit_url)
                    . '" aria-label="'
                    . esc_attr__('Paramètres', 'chassesautresor-com')
                    . '"><i class="fa-solid fa-gear"></i></a>';
            }
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

