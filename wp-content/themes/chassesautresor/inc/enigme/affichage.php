<?php
defined('ABSPATH') || exit;

    // ==================================================
    // 🎨 AFFICHAGE STYLISÉ DES ÉNIGMES
    // ==================================================
    /**
     * 🔹 afficher_enigme_stylisee() → Affiche l’énigme avec son style d’affichage (structure unique + blocs surchargeables)
     * 🔸 enigme_get_partial() → Charge un partiel adapté au style (ex: pirate/images.php), avec fallback global.
     */

    /**     
     * Affiche l’énigme avec son style et son état selon le contexte utilisateur.
     *
     * @param int $enigme_id ID de l’énigme à afficher.
     * @param array $statut_data Données de statut retournées par traiter_statut_enigme().
     */
    function afficher_enigme_stylisee(int $enigme_id, array $statut_data = []): void
    {
        if (get_post_type($enigme_id) !== 'enigme') {
            return;
        }

        if (!empty($statut_data)) {
            // statut_data transmis
        } else {
            // Aucune donnée statut_data transmise à afficher_enigme_stylisee()
        }

        $etat = get_field('enigme_cache_etat_systeme', $enigme_id) ?? 'accessible';

        if ($etat !== 'accessible' && !utilisateur_peut_modifier_enigme($enigme_id)) {
            $chasse_id = recuperer_id_chasse_associee($enigme_id);
            $url       = $chasse_id ? get_permalink($chasse_id) : home_url('/');
            wp_safe_redirect($url);
            exit;
        }

        if (!empty($statut_data['afficher_message'])) {
            echo $statut_data['message_html'];
        }

        $user_id       = get_current_user_id();
        $style         = get_field('enigme_style_affichage', $enigme_id) ?? 'defaut';
        $chasse_id     = recuperer_id_chasse_associee($enigme_id);
        $edition_active = utilisateur_peut_modifier_post($enigme_id);

        $liste      = $chasse_id ? recuperer_enigmes_pour_chasse($chasse_id) : [];
        $menu_items = [];

        foreach ($liste as $post) {
            if (get_post_status($post->ID) !== 'publish') {
                continue;
            }
            if (!get_field('enigme_cache_complet', $post->ID)) {
                continue;
            }
            $etat_sys = get_field('enigme_cache_etat_systeme', $post->ID) ?? 'accessible';
            if ($etat_sys !== 'accessible') {
                continue;
            }
            $classes = [];
            if ($post->ID === $enigme_id) {
                $classes[] = 'active';
            }
            if (!utilisateur_est_engage_dans_enigme($user_id, $post->ID)) {
                $classes[] = 'non-engagee';
            }
            $menu_items[] = sprintf(
                '<li class="%s"><a href="%s">%s</a></li>',
                esc_attr(implode(' ', $classes)),
                esc_url(get_permalink($post->ID)),
                esc_html(get_the_title($post->ID))
            );
        }

        echo '<div class="container container--xl-full">';
        echo '<div class="row enigme-layout">';
        echo '<aside class="enigme-sidebar">';

        if ($edition_active) {
            echo '<button id="toggle-mode-edition-enigme" type="button" ' .
                'class="bouton-edition-toggle bouton-edition-toggle--clair" data-cpt="enigme" aria-label="' .
                esc_attr__('Activer Orgy', 'chassesautresor-com') .
                '"><i class="fa-solid fa-gear"></i></button>';
        }

        if ($chasse_id) {
            $logo = get_the_post_thumbnail($chasse_id, 'thumbnail');
            if ($logo) {
                echo '<div class="enigme-chasse-logo">' . $logo . '</div>';
            }
            $titre_chasse = get_the_title($chasse_id);
            echo '<div class="enigme-chasse-titre">' . esc_html($titre_chasse) . '</div>';
        }

        if (!empty($menu_items)) {
            echo '<ul class="enigme-menu">' . implode('', $menu_items) . '</ul>';
        }

        if ($chasse_id) {
            $url_retour = get_permalink($chasse_id);
            echo '<a href="' . esc_url($url_retour) . '" class="bouton-retour bouton-retour-chasse">';
            echo '<i class="fa-solid fa-arrow-left"></i>';
            echo '<span class="screen-reader-text">' . esc_html__('Retour à la chasse', 'chassesautresor-com') . '</span>';
            echo '</a>';
        }

        echo '<div class="enigme-stats"></div>';

        echo '</aside>';
        echo '<div class="enigme-main">';

        echo '<article class="enigme-content enigme-style-' . esc_attr($style) . ' enigme-header">';
        enigme_get_partial('titre', $style, [
            'post_id' => $enigme_id,
            'user_id' => $user_id,
        ]);
        echo '</article>';

        echo '<section class="enigme-hero">';
        enigme_get_partial('images', $style, [
            'post_id' => $enigme_id,
            'user_id' => $user_id,
        ]);
        echo '</section>';

        echo '<article class="enigme-content enigme-style-' . esc_attr($style) . '">';
        enigme_get_partial('texte', $style, [
            'post_id' => $enigme_id,
            'user_id' => $user_id,
        ]);
        echo '</article>';

        echo '<section class="enigme-participation">';
        enigme_get_partial('bloc-reponse', $style, [
            'post_id' => $enigme_id,
            'user_id' => $user_id,
        ]);
        echo '<div class="enigme-indices"></div>';
        echo '</section>';

        ob_start();
        enigme_get_partial('solution', $style, [
            'post_id' => $enigme_id,
            'user_id' => $user_id,
        ]);
        $solution_html = trim(ob_get_clean());
        if ($solution_html !== '') {
            echo '<section class="enigme-solution">' . $solution_html . '</section>';
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }


    /**
     * Charge un partiel adapté au style d’énigme (ex: pirate/images.php), avec fallback global.
     *
     * @param string $slug   Nom du bloc (titre, images, etc.)
     * @param string $style  Style d’affichage (ex : 'pirate', 'vintage')
     * @param array  $args   Données à transmettre au partial
     */
    function enigme_get_partial(string $slug, string $style = 'defaut', array $args = []): void
    {
        $base_path = "template-parts/enigme/partials";

        // 🧠 Nouveau : on préfixe tous les fichiers par 'enigme-partial-'
        $slug_final = 'enigme-partial-' . $slug;

        $variant = "{$base_path}/{$style}/{$slug_final}.php";
        $fallback = "{$base_path}/{$slug_final}.php";

        if (locate_template($variant)) {
            get_template_part("{$base_path}/{$style}/{$slug_final}", null, $args);
        } elseif (locate_template($fallback)) {
            get_template_part("{$base_path}/{$slug_final}", null, $args);
        } else {
            error_log("❌ Aucun partial trouvé pour $slug (style: $style)");
        }
    }


