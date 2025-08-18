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
     * Build a cache key for rendered enigma blocks.
     *
     * @param string $block    Block identifier.
     * @param int    $post_id  Enigma identifier.
     *
     * @return string
     */
    function enigme_get_render_cache_key(string $block, int $post_id): string
    {
        $version = (int) get_option('enigme_permissions_cache_version', 1);

        return $block . '_' . $post_id . '_' . $version;
    }

    /**
     * Clear cached rendering for a given enigma.
     *
     * @param int $post_id Enigma identifier.
     */
    function enigme_clear_render_cache(int $post_id): void
    {
        wp_cache_delete(enigme_get_render_cache_key('enigme_sidebar', $post_id), 'chassesautresor');
        wp_cache_delete(enigme_get_render_cache_key('enigme_solution', $post_id), 'chassesautresor');
    }

    /**
     * Bump cache version when user permissions change.
     *
     * @param mixed ...$args Unused.
     */
    function enigme_bump_permissions_cache_version(...$args): void
    {
        $version = (int) get_option('enigme_permissions_cache_version', 1);
        update_option('enigme_permissions_cache_version', $version + 1);
    }

    add_action('save_post_enigme', 'enigme_clear_render_cache', 10, 1);
    add_action('set_user_role', 'enigme_bump_permissions_cache_version', 10, 3);
    add_action('profile_update', 'enigme_bump_permissions_cache_version', 10, 2);
    add_action('user_register', 'enigme_bump_permissions_cache_version', 10, 1);
    add_action('deleted_user', 'enigme_bump_permissions_cache_version', 10, 1);
    add_action('added_user_meta', 'enigme_bump_permissions_cache_version', 10, 4);
    add_action('updated_user_meta', 'enigme_bump_permissions_cache_version', 10, 4);
add_action('deleted_user_meta', 'enigme_bump_permissions_cache_version', 10, 4);

    /**
     * Determine if the enigma menu should be displayed for a user.
     *
     * @param int    $user_id     User identifier.
     * @param int    $chasse_id   Associated hunt ID.
     * @param string $chasse_stat Current hunt status.
     *
     * @return bool
     */
    function enigme_user_can_see_menu(int $user_id, int $chasse_id, string $chasse_stat): bool
    {
        if (!$chasse_id) {
            return false;
        }

        $validation_status = get_field('chasse_cache_statut_validation', $chasse_id) ?? '';
        $is_admin          = current_user_can('administrator');
        $is_associated     = utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);
        $is_organizer      = est_organisateur($user_id);

        if (($is_admin || ($is_organizer && $is_associated)) && $validation_status !== 'banni') {
            return true;
        }

        return !in_array($chasse_stat, ['revision', 'a_venir'], true);
    }

    /**
     * Render a bar chart section used for stats.
     *
     * @param string $title         Section title.
     * @param int    $user_rate     Percentage for the current user.
     * @param int    $avg_rate      Average percentage among players.
     * @param string $section_class Additional CSS class for the section.
     *
     * @return string
     */
    function enigme_render_bar_row(string $label, int $rate, string $fill_style = ''): string
    {
        $inside = $rate >= 50;
        $style  = $fill_style === '' ? '' : $fill_style . ';';

        ob_start();
        ?>
        <div class="bar-row">
          <span class="bar-label"><?= esc_html($label); ?></span>
          <div class="bar-wrapper">
            <div class="bar-fill" style="<?= esc_attr($style); ?>width:<?= esc_attr($rate); ?>%;">
              <?php if ($inside) : ?>
                <span class="bar-value"><?= esc_html($rate); ?>%</span>
              <?php endif; ?>
            </div>
            <?php if (!$inside) : ?>
              <span class="bar-value bar-value--outside" style="left:calc(<?= esc_attr($rate); ?>% + 4px);">
                <?= esc_html($rate); ?>%
              </span>
            <?php endif; ?>
          </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    function enigme_render_bar_section(string $title, int $user_rate, int $avg_rate, string $section_class): string
    {
        ob_start();
        ?>
        <section class="<?= esc_attr($section_class); ?>">
          <h3><?= esc_html($title); ?></h3>
          <div class="stats-bar-chart">
            <?= enigme_render_bar_row(esc_html__('Vous', 'chassesautresor-com'), $user_rate, 'background-color:var(--color-primary)'); ?>
            <?= enigme_render_bar_row(esc_html__('Moyenne', 'chassesautresor-com'), $avg_rate); ?>
          </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    function enigme_render_bar_subsection(string $title, int $user_rate, int $avg_rate, string $section_class): string
    {
        ob_start();
        ?>
        <div class="<?= esc_attr($section_class); ?>">
          <p class="aside-subsection-title"><?= esc_html($title); ?></p>
          <div class="stats-bar-chart">
            <?= enigme_render_bar_row(esc_html__('Vous', 'chassesautresor-com'), $user_rate, 'background-color:var(--color-primary)'); ?>
            <?= enigme_render_bar_row(esc_html__('Moyenne', 'chassesautresor-com'), $avg_rate); ?>
          </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Build engagement histogram HTML for the sidebar.
     *
     * @param int|null $chasse_id Hunt identifier.
     * @param int      $user_id   Current user identifier.
     *
     * @return string
     */
    function enigme_sidebar_engagement_html(?int $chasse_id, int $user_id): string
    {
        if (!$chasse_id || !$user_id) {
            return '';
        }

        $cache_key = 'enigme_sidebar_engagement_' . $chasse_id . '_' . $user_id;
        $data      = wp_cache_get($cache_key, 'chassesautresor');

        if ($data === false) {
            $enigme_ids    = recuperer_ids_enigmes_pour_chasse($chasse_id);
            $total_enigmes = count($enigme_ids);
            $user_rate     = 0;

            if ($total_enigmes > 0) {
                global $wpdb;
                $table        = $wpdb->prefix . 'engagements';
                $placeholders = implode(',', array_fill(0, count($enigme_ids), '%d'));
                $sql          = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT enigme_id) FROM {$table} WHERE user_id = %d AND enigme_id IN ($placeholders)",
                    $user_id,
                    ...$enigme_ids
                );
                $engagees = (int) $wpdb->get_var($sql);
                $user_rate = (100 * $engagees) / $total_enigmes;
            }

            $avg_rate = chasse_calculer_taux_engagement($chasse_id);
            $data     = [
                'user' => (int) round($user_rate),
                'avg'  => (int) round($avg_rate),
            ];

            wp_cache_set($cache_key, $data, 'chassesautresor', HOUR_IN_SECONDS);
        }

        return enigme_render_bar_subsection(
            esc_html__('Engagements', 'chassesautresor-com'),
            $data['user'],
            $data['avg'],
            'enigme-engagement'
        );
    }

    /**
     * Build resolution histogram HTML for the sidebar.
     *
     * @param int|null $chasse_id Hunt identifier.
     * @param int      $user_id   Current user identifier.
     *
     * @return string
     */
    function enigme_sidebar_progression_html(?int $chasse_id, int $user_id): string
    {
        if (!$chasse_id || !$user_id) {
            return '';
        }

        $cache_key = 'enigme_sidebar_progression_' . $chasse_id . '_' . $user_id;
        $data      = wp_cache_get($cache_key, 'chassesautresor');

        if ($data === false) {
            $enigme_ids = recuperer_ids_enigmes_pour_chasse($chasse_id);

            if (!$enigme_ids) {
                $user_rate = 0;
                $avg_rate  = 0;
            } else {
                $validables = array_filter($enigme_ids, function ($id) {
                    return get_field('enigme_mode_validation', $id) !== 'aucune';
                });
                $total_validables = count($validables);

                if ($total_validables === 0) {
                    $user_rate = 0;
                    $avg_rate  = 0;
                } else {
                    global $wpdb;
                    $table        = $wpdb->prefix . 'enigme_statuts_utilisateur';
                    $placeholders = implode(',', array_fill(0, $total_validables, '%d'));
                    $sql          = $wpdb->prepare(
                        "SELECT COUNT(DISTINCT enigme_id) FROM {$table} WHERE user_id = %d AND statut IN ('resolue','terminee','terminée') AND enigme_id IN ($placeholders)",
                        $user_id,
                        ...$validables
                    );
                    $solved    = (int) $wpdb->get_var($sql);
                    $user_rate = (100 * $solved) / $total_validables;
                    $avg_rate  = chasse_calculer_taux_progression($chasse_id);
                }
            }

            $data = [
                'user' => (int) round($user_rate),
                'avg'  => (int) round($avg_rate),
            ];

            wp_cache_set($cache_key, $data, 'chassesautresor', HOUR_IN_SECONDS);
        }

        return enigme_render_bar_subsection(
            esc_html__('Résolution', 'chassesautresor-com'),
            $data['user'],
            $data['avg'],
            'enigme-resolution'
        );
    }

    /**
     * Renders the sidebar of the enigma layout.
     *
     * @param int      $enigme_id      Enigma identifier.
     * @param bool     $edition_active Whether the edition mode is active.
     * @param int|null $chasse_id      Associated hunt ID.
     * @param array    $menu_items     Menu items to display.
     */
    function render_enigme_sidebar(int $enigme_id, bool $edition_active, ?int $chasse_id, array $menu_items): void
    {
        $cache_key = enigme_get_render_cache_key('enigme_sidebar', $enigme_id);
        $html      = wp_cache_get($cache_key, 'chassesautresor');

        if ($html === false) {
            ob_start();
            echo '<aside class="menu-lateral">';

            echo '<div class="menu-lateral__header">';
            if ($chasse_id) {
                $url_chasse = get_permalink($chasse_id);
                $titre      = get_the_title($chasse_id);
                echo '<h2 class="menu-lateral__title"><a href="' . esc_url($url_chasse) . '">' . esc_html($titre) . '</a></h2>';
            }

            if ($edition_active) {
                echo '<button id="toggle-mode-edition-enigme" type="button" ' .
                    'class="bouton-edition-toggle bouton-edition-toggle--clair menu-lateral__edition-toggle" data-cpt="enigme" aria-label="' .
                    esc_attr__('Activer Orgy', 'chassesautresor-com') .
                    '"><i class="fa-solid fa-gear"></i></button>';
            }
            echo '</div>';

            if ($chasse_id) {
                $logo = get_the_post_thumbnail($chasse_id, 'thumbnail');
                if ($logo) {
                    echo '<div class="enigme-chasse-logo">' . $logo . '</div>';
                }
            }

            if (!empty($menu_items)) {
                echo '<section class="enigme-navigation">';
                echo '<h3>' . esc_html__('Énigmes', 'chassesautresor-com') . '</h3>';
                echo '<ul class="enigme-menu">' . implode('', $menu_items) . '</ul>';
                echo '</section>';
            }

            echo '<section class="enigme-progression"><h3>' .
                esc_html__('Progression', 'chassesautresor-com') .
                '</h3>%STATS%</section>';
            echo '</aside>';
            $html = ob_get_clean();
            wp_cache_set($cache_key, $html, 'chassesautresor', HOUR_IN_SECONDS);
        }

        $user_id    = get_current_user_id();
        $stats_html = enigme_sidebar_engagement_html($chasse_id, $user_id)
            . enigme_sidebar_progression_html($chasse_id, $user_id);
        echo str_replace('%STATS%', $stats_html, $html);
    }

    /**
     * Render the hero section for the enigma.
     *
     * @param int    $enigme_id Enigma identifier.
     * @param string $style     Display style.
     * @param int    $user_id   Current user ID.
     */
    function render_enigme_hero(int $enigme_id, string $style, int $user_id): void
    {
        echo '<section class="hero-visuel">';
        enigme_get_partial(
            'images',
            $style,
            [
                'post_id' => $enigme_id,
                'user_id' => $user_id,
            ]
        );
        echo '</section>';
    }

    /**
     * Render the title and subtitle section of the enigma.
     *
     * @param int    $enigme_id Enigma identifier.
     * @param string $style     Display style.
     * @param int    $user_id   Current user ID.
     */
    function render_enigme_title(int $enigme_id, string $style, int $user_id): void
    {
        echo '<section class="bloc-titre">';
        enigme_get_partial(
            'titre',
            $style,
            [
                'post_id' => $enigme_id,
                'user_id' => $user_id,
            ]
        );
        echo '</section>';
    }

    /**
     * Render the textual content section of the enigma.
     *
     * @param int    $enigme_id Enigma identifier.
     * @param string $style     Display style.
     * @param int    $user_id   Current user ID.
     */
    function render_enigme_content(int $enigme_id, string $style, int $user_id): void
    {
        echo '<article class="contenu-principal">';
        enigme_get_partial(
            'texte',
            $style,
            [
                'post_id' => $enigme_id,
                'user_id' => $user_id,
            ]
        );
        echo '</article>';
    }

    /**
     * Render the participation section of the enigma.
     *
     * @param int    $enigme_id Enigma identifier.
     * @param string $style     Display style.
     * @param int    $user_id   Current user ID.
     */
    function render_enigme_participation(int $enigme_id, string $style, int $user_id): void
    {
        echo '<section class="participation">';
        echo '<div class="zone-reponse">';
        enigme_get_partial(
            'bloc-reponse',
            $style,
            [
                'post_id' => $enigme_id,
                'user_id' => $user_id,
            ]
        );
        echo '</div>';

        $hints = get_field('indices', $enigme_id);
        if (!empty($hints)) {
            echo '<div class="zone-indices"><h3>' . esc_html__('Indices', 'chassesautresor-com') . '</h3></div>';
        }
        echo '</section>';
    }

    /**
     * Render the solution section of the enigma.
     *
     * @param int    $enigme_id Enigma identifier.
     * @param string $style     Display style.
     * @param int    $user_id   Current user ID.
     */
    function render_enigme_solution(int $enigme_id, string $style, int $user_id): void
    {
        $cache_key = enigme_get_render_cache_key('enigme_solution', $enigme_id);
        $html      = wp_cache_get($cache_key, 'chassesautresor');

        if ($html === false) {
            ob_start();
            ob_start();
            enigme_get_partial(
                'solution',
                $style,
                [
                    'post_id' => $enigme_id,
                    'user_id' => $user_id,
                ]
            );
            $content = trim(ob_get_clean());
            if ($content !== '') {
                echo '<section class="solution">';
                echo '<details><summary>' . esc_html__('Voir la solution', 'chassesautresor-com') . '</summary>';
                echo '<div class="solution-content">' . $content . '</div>';
                echo '</details>';
                echo '</section>';
            }
            $html = ob_get_clean();
            wp_cache_set($cache_key, $html, 'chassesautresor', HOUR_IN_SECONDS);
        }

        echo $html;
    }

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

        $user_id        = get_current_user_id();
        $style          = get_field('enigme_style_affichage', $enigme_id) ?? 'defaut';
        $chasse_id      = recuperer_id_chasse_associee($enigme_id);
        $edition_active = utilisateur_peut_modifier_post($enigme_id);

        $menu_items  = [];
        $liste       = [];
        $chasse_stat = $chasse_id ? get_field('chasse_cache_statut', $chasse_id) : '';
        $show_menu   = enigme_user_can_see_menu($user_id, $chasse_id, $chasse_stat);
        $skip_checks = $chasse_stat === 'termine';
        $is_privileged = current_user_can('administrator')
            || (est_organisateur($user_id)
            && utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id));

        if ($show_menu) {
            $cache_key = 'enigmes_chasse_' . $chasse_id;
            $liste     = wp_cache_get($cache_key, 'chassesautresor');
            if ($liste === false) {
                $liste = recuperer_enigmes_pour_chasse($chasse_id);
                wp_cache_set($cache_key, $liste, 'chassesautresor', HOUR_IN_SECONDS);
            }
        }

        $submenu_items = [];

        foreach ($liste as $post) {
            if (!$is_privileged) {
                if (get_post_status($post->ID) !== 'publish') {
                    continue;
                }
                if (!get_field('enigme_cache_complet', $post->ID)) {
                    continue;
                }
            }

            $classes = [];

            if (!$skip_checks) {
                $etat_sys = get_field('enigme_cache_etat_systeme', $post->ID) ?? 'accessible';
                if (in_array($etat_sys, ['invalide', 'cache_invalide'], true)) {
                    continue;
                }

                if (in_array($etat_sys, ['bloquee_date', 'bloquee_chasse', 'bloquee_pre_requis'], true)) {
                    $classes[] = 'bloquee';
                } else {
                    $statut_user = enigme_get_statut_utilisateur($post->ID, $user_id);
                    if (in_array($statut_user, ['resolue', 'terminee'], true)) {
                        $classes[] = 'succes';
                    } elseif (
                        $statut_user === 'non_commencee'
                        && !utilisateur_est_engage_dans_enigme($user_id, $post->ID)
                    ) {
                        $classes[] = 'non-engagee';
                    }
                }
            }

            if ($post->ID === $enigme_id) {
                $classes[] = 'active';
            }

            $submenu_items[] = sprintf(
                '<li class="%s"><a href="%s">%s</a></li>',
                esc_attr(implode(' ', $classes)),
                esc_url(get_permalink($post->ID)),
                esc_html(get_the_title($post->ID))
            );
        }

        if ($show_menu && !empty($submenu_items)) {
            $menu_items = $submenu_items;
        }

        echo '<div class="container container--xl-full enigme-layout">';
        render_enigme_sidebar($enigme_id, $edition_active, $chasse_id, $menu_items);
        echo '<main class="page-enigme enigme-style-' . esc_attr($style) . '">';
        render_enigme_title($enigme_id, $style, $user_id);
        render_enigme_hero($enigme_id, $style, $user_id);
        render_enigme_content($enigme_id, $style, $user_id);
        render_enigme_participation($enigme_id, $style, $user_id);
        render_enigme_solution($enigme_id, $style, $user_id);
        echo '</main>';
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
            cat_debug("❌ Aucun partial trouvé pour $slug (style: $style)");
        }
    }


