<?php

declare(strict_types=1);

if (!function_exists('sidebar_prepare_chasse_nav')) {
    /**
     * Prepare navigation items for a hunt sidebar.
     *
     * @param int $chasse_id         Hunt identifier.
     * @param int $user_id           Current user identifier.
     * @param int $current_enigme_id Current enigma identifier.
     *
     * @return array{
     *     menu_items:array,
     *     peut_ajouter_enigme:bool,
     *     total_enigmes:int,
     *     has_incomplete_enigme:bool,
     *     visible_ids:array
     * }
     */
    function sidebar_prepare_chasse_nav(
        int $chasse_id,
        int $user_id,
        int $current_enigme_id = 0
    ): array
    {
        $ids                   = recuperer_enigmes_associees($chasse_id);
        $all_enigmes           = $ids
            ? get_posts([
                'post_type'      => 'enigme',
                'post__in'       => $ids,
                'orderby'        => 'post__in',
                'post_status'    => ['publish', 'pending'],
                'posts_per_page' => -1,
            ])
            : [];
        $submenu_items         = [];
        $total_enigmes         = count($all_enigmes);
        $has_incomplete_enigme = false;
        $chasse_validation     = get_field('chasse_cache_statut_validation', $chasse_id) ?? '';
        $chasse_statut         = get_field('chasse_cache_statut', $chasse_id) ?? '';

        foreach ($all_enigmes as $post_check) {
            if (!get_field('enigme_cache_complet', $post_check->ID)) {
                $has_incomplete_enigme = true;
                break;
            }
        }

        $peut_ajouter_enigme = function_exists('utilisateur_peut_ajouter_enigme')
            ? utilisateur_peut_ajouter_enigme($chasse_id)
            : false;

        $is_privileged = (
                function_exists('user_can') && user_can($user_id, 'manage_options')
            ) || (
                function_exists('utilisateur_est_organisateur_associe_a_chasse')
                && utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
            );

        $visible_ids = [];

        foreach ($all_enigmes as $post) {
            $cta = function_exists('get_cta_enigme')
                ? get_cta_enigme($post->ID, $user_id)
                : [
                    'etat_systeme'       => get_field('enigme_cache_etat_systeme', $post->ID) ?? 'accessible',
                    'statut_utilisateur' => enigme_get_statut_utilisateur($post->ID, $user_id),
                ];

            if (
                !$is_privileged
                && in_array($cta['etat_systeme'], ['bloquee_date', 'bloquee_pre_requis'], true)
            ) {
                continue;
            }

            $classes            = [];
            $complet            = (bool) get_field('enigme_cache_complet', $post->ID);
            $mode_valid         = get_field('enigme_mode_validation', $post->ID);
            $display_validation = false;
            $title_validation   = '';

            if ($complet) {
                if (
                    $chasse_validation === 'valide'
                    && in_array($chasse_statut, ['payante', 'termine', 'en_cours'], true)
                ) {
                    $display_validation = true;
                } elseif (
                    $is_privileged
                    && in_array($chasse_validation, ['creation', 'correction', 'en_attente'], true)
                    && in_array($chasse_statut, ['revision', 'a_venir'], true)
                ) {
                    $display_validation = true;
                }
            }

            if (in_array($cta['etat_systeme'], ['bloquee_date', 'bloquee_pre_requis'], true)) {
                $classes[] = 'bloquee';
                $classes[] = str_replace('_', '-', $cta['etat_systeme']);
            } elseif ($cta['etat_systeme'] === 'bloquee_chasse') {
                if (!$complet) {
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

                if ($display_validation) {
                    if ($mode_valid === 'automatique') {
                        $classes[]       = 'validation-auto';
                        $title_validation = esc_attr__(
                            'validation instantanée en ligne de votre tentative',
                            'chassesautresor-com'
                        );
                    } elseif ($mode_valid === 'manuelle') {
                        $classes[]       = 'validation-manuelle';
                        $title_validation = esc_attr__(
                            'validation manuelle de votre tentative par l\'organisateur',
                            'chassesautresor-com'
                        );
                    } else {
                        $classes[]       = 'validation-aucune';
                        $title_validation = esc_attr__(
                            'pas de système de validation en ligne pour cette énigme',
                            'chassesautresor-com'
                        );
                    }
                }
            }

            if (in_array($chasse_validation, ['creation', 'correction'], true)) {
                $classes[] = $complet ? 'complete' : 'incomplete';
            }

            if ($post->ID === $current_enigme_id) {
                $classes[] = 'active';
            }

            $edit = '';
            if (
                function_exists('utilisateur_peut_modifier_enigme')
                && utilisateur_peut_modifier_enigme($post->ID)
            ) {
                if ($post->ID === $current_enigme_id) {
                    $edit = '<button id="toggle-mode-edition-enigme" type="button"'
                        . ' class="enigme-menu__edit" aria-label="'
                        . esc_attr__("Paramètres", "chassesautresor-com")
                        . '"><i class="fa-solid fa-gear"></i></button>';
                } else {
                    $status = function_exists('get_post_status')
                        ? get_post_status($post->ID)
                        : 'publish';
                    $tab = ($status === 'publish' && get_field('enigme_cache_complet', $post->ID))
                        ? 'stats'
                        : 'param';
                    $base_url = get_permalink($post->ID);
                    $edit_url = function_exists('add_query_arg')
                        ? add_query_arg(['edition' => 'open', 'tab' => $tab], $base_url)
                        : $base_url . '?edition=open&tab=' . $tab;
                    $edit = '<a class="enigme-menu__edit" href="' . esc_url($edit_url)
                        . '" aria-label="'
                        . esc_attr__("Paramètres", "chassesautresor-com")
                        . '"><i class="fa-solid fa-gear"></i></a>';
                }
            }

            $title        = esc_html(get_the_title($post->ID));
            $aria_current = $post->ID === $current_enigme_id ? ' aria-current="page"' : '';
            $link = '<a href="' . esc_url(get_permalink($post->ID)) . '"' . $aria_current . '>'
                . $title . '</a>';
            $submenu_items[] = sprintf(
                '<li class="%s" data-enigme-id="%d"%s>%s%s</li>',
                esc_attr(implode(' ', $classes)),
                $post->ID,
                $title_validation ? ' title="' . $title_validation . '"' : '',
                $link,
                $edit
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
