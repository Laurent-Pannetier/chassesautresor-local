<?php

declare(strict_types=1);

/**
 * Génère le bouton d'action et le message d'explication pour une chasse.
 *
 * @param int      $chasse_id ID de la chasse.
 * @param int|null $user_id   ID de l'utilisateur (par défaut : utilisateur courant).
 *
 * @return array{cta_html:string, cta_message:string}
 */
function generer_cta_chasse(int $chasse_id, ?int $user_id = null): array
{
    $user_id    = $user_id ?? get_current_user_id();
    $permalink  = get_permalink($chasse_id);
    $statut     = get_field('chasse_cache_statut', $chasse_id) ?: 'revision';
    $validation = get_field('chasse_cache_statut_validation', $chasse_id);
    $date_debut = get_field('chasse_infos_date_debut', $chasse_id);
    $date_fin   = get_field('chasse_infos_date_fin', $chasse_id);

    // 🧑‍💻 Utilisateur non connecté
    if (! $user_id) {
        $login_url = wp_login_url($permalink);

        return [
            'cta_html'    => sprintf(
                '<a href="%s" class="bouton-cta bouton-cta--color">%s</a>',
                esc_url($login_url),
                esc_html__('S\'identifier', 'chassesautresor-com')
            ),
            'cta_message' => '',
            'type'        => 'connexion',
        ];
    }

    if (function_exists('peut_valider_chasse') && peut_valider_chasse($chasse_id, $user_id)) {
        return [
            'cta_html'    => render_form_validation_chasse($chasse_id),
            'cta_message' => '',
            'type'        => 'validation',
        ];
    }

    // 🔐 Admin or organiser info
    $admin_override = $GLOBALS['force_admin_override'] ?? null;
    $is_admin = $admin_override !== null ? (bool) $admin_override : current_user_can('administrator');
    $orga_override = $GLOBALS['force_organisateur_override'] ?? null;
    $is_orga = $orga_override !== null ? (bool) $orga_override : utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);

    if ($validation === 'en_attente') {
        if ($is_orga) {
            return [
                'cta_html'    => render_form_annulation_validation_chasse($chasse_id),
                'cta_message' => '',
                'type'        => 'annuler_validation',
            ];
        }
        return [
            'cta_html'    => '<span class="bouton-cta bouton-cta--pending" aria-disabled="true">'
                . esc_html__( 'Demande de validation en cours', 'chassesautresor-com' )
                . '</span>',
            'cta_message' => '',
            'type'        => 'en_attente',
        ];
    }

    // 🔐 Admin or organiser: front-end edition
    if ($is_orga && in_array($validation, ['creation', 'correction'], true)) {
        $edition_url = function_exists('add_query_arg')
            ? add_query_arg(['edition' => 'open', 'tab' => 'param'], $permalink)
            : $permalink . '?edition=open&tab=param';

        return [
            'cta_html'    => sprintf(
                '<a href="%s" class="bouton-secondaire">%s</a>',
                esc_url($edition_url),
                esc_html__('Continuer l’édition', 'chassesautresor-com')
            ),
            'cta_message' => '',
            'type'        => 'edition',
        ];
    }

    if (
        $is_orga
        && in_array($statut, ['en_cours', 'payante'], true)
        && in_array($validation, ['valide', 'active'], true)
    ) {
        $stats_url = function_exists('add_query_arg')
            ? add_query_arg(['edition' => 'open', 'tab' => 'stats'], $permalink)
            : $permalink . '?edition=open&tab=stats';

        return [
            'cta_html'    => sprintf(
                '<a href="%s" class="bouton-secondaire">%s</a>',
                esc_url($stats_url),
                esc_html__('Statistiques', 'chassesautresor-com')
            ),
            'cta_message' => '',
            'type'        => 'statistiques',
        ];
    }

    if ($is_admin || $is_orga) {
        return [
            'cta_html'    => sprintf(
                '<button class="bouton-cta" disabled>%s</button>',
                esc_html__( 'Participer', 'chassesautresor-com' )
            ),
            'cta_message' => '',
            'type'        => 'indisponible',
        ];
    }

    // ✅ Déjà engagé
    $engage_override = $GLOBALS['force_engage_override'] ?? null;
    $est_engage = $engage_override !== null ? (bool) $engage_override : utilisateur_est_engage_dans_chasse($user_id, $chasse_id);
    if ($est_engage) {
        return [
            'cta_html'    => '<a href="#chasse-enigmes-wrapper" class="bouton-secondaire">' . esc_html__('Voir mes énigmes', 'chassesautresor-com') . '</a>',
            'cta_message' => '<p>✅ ' . esc_html__('Vous participez à cette chasse', 'chassesautresor-com') . '</p>',
            'type'        => 'engage',
        ];
    }

    // ❌ Chasse non validée
    if ($validation !== 'valide') {
        return ['cta_html' => '', 'cta_message' => '', 'type' => ''];
    }

    $html    = '';
    $message = '';
    $type    = '';

    if ($statut === 'a_venir') {
        $html = sprintf(
            '<button class="bouton-cta" disabled>%s</button>',
            esc_html__('Indisponible', 'chassesautresor-com')
        );
        $type = 'indisponible';
        $message = $date_debut
            ? sprintf(
                __('Chasse disponible à partir du %s', 'chassesautresor-com'),
                date_i18n('d/m/Y \à H:i', strtotime($date_debut))
            )
            : __('Chasse disponible prochainement', 'chassesautresor-com');
    } elseif ($statut === 'en_cours' || $statut === 'payante') {
        $cout_points        = (int) get_field('chasse_infos_cout_points', $chasse_id);
        $points_disponibles = get_user_points($user_id);

        if ($statut === 'payante' && $cout_points > 0 && $points_disponibles < $cout_points) {
            $html = sprintf(
                '<button class="bouton-cta" disabled>%s</button>',
                esc_html__( 'Points insuffisants', 'chassesautresor-com' )
            );
            $points_manquants = $cout_points - $points_disponibles;
            $points_lien      = sprintf(
                '<a href="%s">%s</a>',
                esc_url( site_url( '/boutique' ) ),
                esc_html__( 'points', 'chassesautresor-com' )
            );
            $message = sprintf(
                __( 'Il vous manque %1$d %2$s pour participer à cette chasse.', 'chassesautresor-com' ),
                $points_manquants,
                $points_lien
            );
            $type = 'indisponible';
        } else {
            // 🔓 Participation gratuite à ce stade, engagement simple
            $html  = '<form method="post" action="' . esc_url(site_url('/traitement-engagement')) . '" class="cta-chasse-form">';
            $html .= '<input type="hidden" name="chasse_id" value="' . esc_attr($chasse_id) . '">';
            $html .= wp_nonce_field('engager_chasse_' . $chasse_id, 'engager_chasse_nonce', true, false);
            $html .= sprintf(
                '<button type="submit" class="bouton-cta bouton-cta--color">%s</button>',
                esc_html__('Participer', 'chassesautresor-com')
            );
            $html .= '</form>';
            $message = '';
            $type    = 'engager';
        }
    } elseif ($statut === 'termine') {
        // ✅ Chasse terminée : engagement requis pour accéder aux énigmes
        $html  = '<form method="post" action="' . esc_url(site_url('/traitement-engagement')) . '" class="cta-chasse-form">';
        $html .= '<input type="hidden" name="chasse_id" value="' . esc_attr($chasse_id) . '">';
        $html .= wp_nonce_field('engager_chasse_' . $chasse_id, 'engager_chasse_nonce', true, false);
        $html .= sprintf(
            '<button type="submit" class="bouton-cta bouton-cta--color">%s</button>',
            esc_html__('Redécouvrir', 'chassesautresor-com')
        );
        $html .= '</form>';
        $type = 'engager';
        $message = '';
    }

    return [
        'cta_html'    => $html,
        'cta_message' => $message,
        'type'        => $type,
    ];
}

/**
 * Génère le formulaire de demande de validation pour une chasse.
 *
 * @param int $chasse_id ID de la chasse.
 * @return string HTML du formulaire.
 */
function render_form_validation_chasse(int $chasse_id): string
{
    $nonce = wp_create_nonce('validation_chasse_' . $chasse_id);
    ob_start();
?>
    <form method="post" action="<?= esc_url(site_url('/traitement-validation-chasse')); ?>" class="form-validation-chasse">
        <input type="hidden" name="chasse_id" value="<?= esc_attr($chasse_id); ?>">
        <input type="hidden" name="validation_chasse_nonce" value="<?= esc_attr($nonce); ?>">
        <input type="hidden" name="demande_validation_chasse" value="1">
        <button type="submit" class="bouton-cta bouton-cta--color bouton-validation-chasse">
            <?= esc_html__( 'Demander la validation', 'chassesautresor-com' ); ?>
        </button>
    </form>
<?php
    return ob_get_clean();
}

/**
 * Génère le formulaire d'annulation d'une demande de validation.
 *
 * @param int $chasse_id ID de la chasse.
 * @return string HTML du formulaire.
 */
function render_form_annulation_validation_chasse(int $chasse_id): string
{
    $nonce = wp_create_nonce('annulation_validation_chasse_' . $chasse_id);
    ob_start();
?>
    <form method="post" action="<?= esc_url(admin_url('admin-ajax.php')); ?>" class="form-annulation-validation-chasse">
        <input type="hidden" name="action" value="annulation_validation_chasse">
        <input type="hidden" name="chasse_id" value="<?= esc_attr($chasse_id); ?>">
        <input type="hidden" name="annulation_validation_chasse_nonce" value="<?= esc_attr($nonce); ?>">
        <input type="hidden" name="annuler_validation_chasse" value="1">
        <button type="submit" class="bouton-cta bouton-cta--color bouton-annulation-validation-chasse">
            <?= esc_html__( 'Annuler la demande', 'chassesautresor-com' ); ?>
        </button>
    </form>
<?php
    return ob_get_clean();
}

/**
 * Retourne la première chasse pouvant être soumise à validation pour un utilisateur.
 *
 * @param int $user_id ID utilisateur.
 * @return int|null ID de la chasse ou null.
 */
function trouver_chasse_a_valider(int $user_id): ?int
{
    $organisateur_id = get_organisateur_from_user($user_id);
    if (!$organisateur_id) {
        return null;
    }

    $query   = get_chasses_de_organisateur($organisateur_id);
    $chasses = is_a($query, 'WP_Query') ? $query->posts : (array) $query;

    foreach ($chasses as $chasse_id) {
        $chasse_id = (int) $chasse_id;
        if (function_exists('peut_valider_chasse') && peut_valider_chasse($chasse_id, $user_id)) {
            return $chasse_id;
        }
    }

    return null;
}

function traiter_annulation_validation_chasse(): void
{
    ChassesAuTresor\Core\Progress\HuntValidationAjaxHandler::cancel();
}

/**
 * Retourne le bloc d'incitation à la validation d'une chasse pour mise à jour dynamique.
 *
 * @hook wp_ajax_actualiser_cta_validation_chasse
 * @return void
 */
function actualiser_cta_validation_chasse(): void
{
    ChassesAuTresor\Core\Progress\HuntValidationAjaxHandler::refreshCta();
}

/**
 * Formate le libellé internationalisé du nombre de joueurs.
 *
 * @param int $nombre Nombre de joueurs.
 * @return string Libellé formaté.
 */
function formater_nombre_joueurs(int $nombre): string
{
    $quantite = ($nombre === 0 || $nombre === 1) ? 1 : $nombre;

    return sprintf(
        _n('%d joueur', '%d joueurs', $quantite, 'chassesautresor-com'),
        $nombre
    );
}

function cat_build_hunt_validation_cta(int $chasse_id, int $enigme_id): string
{
    verifier_ou_mettre_a_jour_cache_complet($enigme_id);
    verifier_ou_mettre_a_jour_cache_complet($chasse_id);

    $posts = get_posts([
        'post_type'      => 'enigme',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'post_status'    => ['publish', 'pending', 'draft'],
        'meta_query'     => [[
            'key'     => 'enigme_chasse_associee',
            'value'   => $chasse_id,
            'compare' => 'LIKE',
        ]],
    ]);

    foreach ($posts as $p) {
        verifier_ou_mettre_a_jour_cache_complet($p->ID);
    }

    ob_start();
    if (
        function_exists('peut_valider_chasse')
        && peut_valider_chasse($chasse_id, get_current_user_id())
    ) {
        echo '<div id="cta-validation-chasse" class="cta-chasse-row">';
        echo '<div class="cta-action">' . render_form_validation_chasse($chasse_id) . '</div>';
        echo '<div class="cta-message" aria-live="polite"></div>';
        echo '</div>';
    }
    $html = ob_get_clean();

    return (string) $html;
}

if (class_exists(ChassesAuTresor\Core\Progress\HuntValidationAjaxHandler::class)) {
    ChassesAuTresor\Core\Progress\HuntValidationAjaxHandler::configure(
        static function (int $hunt_id, int $riddle_id): string {
            return cat_build_hunt_validation_cta($hunt_id, $riddle_id);
        }
    );
}
