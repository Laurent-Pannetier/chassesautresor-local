<?php
defined('ABSPATH') || exit;


//
// 1. 📦 FONCTIONS LIÉES À UNE CHASSE
// 2. 📦 AFFICHAGE
//


// ==================================================
// 📦 FONCTIONS LIÉES À UNE CHASSE
// ==================================================
/**
 * 🔹 recuperer_infos_chasse → Récupérer les informations essentielles d’une chasse.
 * 🔹 chasse_get_champs → Récupérer les champs principaux et cachés structurés d'une chasse
 * 🔹 verifier_souscription_chasse → Vérifier si un utilisateur souscrit à une chasse pour la première fois en souscrivant à une énigme.
 * 🔹 acf/validate_value/name=date_de_fin (function) → Valider les incohérences de dates dans les chasses.
 * 🔹 gerer_chasse_terminee → Déclencher toutes les actions nécessaires lorsqu’une chasse est terminée.
 */



/**
 * Récupère les informations essentielles d'une chasse.
 *
 * @param int $chasse_id ID de la chasse.
 * @return array Associatif avec 'lot', 'date_de_debut', 'date_de_fin'.
 */
function recuperer_infos_chasse($chasse_id)
{
    $champs = get_fields($chasse_id);
    return [
        'lot' => $champs['lot'] ?? 'Non spécifié',
        'date_de_debut' => $champs['date_de_debut'] ?? 'Non spécifiée',
        'date_de_fin' => $champs['date_de_fin'] ?? 'Non spécifiée',
    ];
}



/**
 * Récupère les champs principaux et cachés d'une chasse.
 *
 * @param int $chasse_id ID de la chasse.
 * @return array
 */
function chasse_get_champs($chasse_id)
{
    return [
        'lot' => get_field('chasse_infos_recompense_texte', $chasse_id, false) ?? '',
        'titre_recompense' => get_field('chasse_infos_recompense_titre', $chasse_id) ?? '',
        'valeur_recompense' => get_field('chasse_infos_recompense_valeur', $chasse_id) ?? '',
        'cout_points' => get_field('chasse_infos_cout_points', $chasse_id) ?? 0,
        // Lecture directe des dates pour éviter un éventuel cache ACF
        'date_debut' => (function () use ($chasse_id) {
            $val = get_field('chasse_infos_date_debut', $chasse_id);
            if (!$val) {
                $meta = get_post_meta($chasse_id, 'chasse_infos_date_debut', true);
                $val = $meta;
            }
            return $val;
        })(),
        'date_fin' => (function () use ($chasse_id) {
            $val = get_field('chasse_infos_date_fin', $chasse_id);
            if (!$val) {
                $meta = get_post_meta($chasse_id, 'chasse_infos_date_fin', true);
                $val = $meta;
            }
            return $val;
        })(),
        'illimitee' => get_field('chasse_infos_duree_illimitee', $chasse_id) ?? false,
        'nb_max' => get_field('chasse_infos_nb_max_gagants', $chasse_id) ?? 0,
        'date_decouverte' => get_field('chasse_cache_date_decouverte', $chasse_id),
        'gagnants' => get_field('chasse_cache_gagnants', $chasse_id) ?? '',
        'mode_fin' => get_field('chasse_mode_fin', $chasse_id) ?? 'automatique',
        'current_stored_statut' => get_field('chasse_cache_statut', $chasse_id),
    ];
}



/**
 * Vérifie si un utilisateur souscrit à une chasse pour la première fois en souscrivant à une énigme.
 *
 * @param int $user_id ID de l'utilisateur
 * @param int $enigme_id ID de l'énigme souscrite
 */
function verifier_souscription_chasse($user_id, $enigme_id)
{

    if (!$user_id || !$enigme_id) {
        error_log("🚨 ERREUR : ID utilisateur ou énigme manquant.");
        return;
    }

    // 🏴‍☠️ Récupération de la chasse associée à l’énigme
    $chasse_id = get_field('chasse_associee', $enigme_id);
    if (!$chasse_id) {
        error_log("⚠️ Aucune chasse associée à l'énigme ID {$enigme_id}");
        return;
    }

    // 🔍 Vérification si l'utilisateur a déjà joué une énigme de cette chasse
    $enigmes_associees = get_field('enigmes_associees', $chasse_id);
    if (!$enigmes_associees || !is_array($enigmes_associees)) {
        error_log("⚠️ Pas d'énigmes associées à la chasse ID {$chasse_id}");
        return;
    }

    foreach ($enigmes_associees as $eid) {
        $statut = get_user_meta($user_id, "statut_enigme_{$eid}", true);

        // 🚫 Si une énigme a déjà été souscrite, tentée ou trouvée, la chasse est déjà souscrite
        if ($statut && $statut !== 'non_souscrit') {
            error_log("🔄 L'utilisateur ID {$user_id} a déjà interagi avec l'énigme ID {$eid}. Chasse ID {$chasse_id} déjà souscrite.");
            return;
        }
    }

    error_log("🔍 Vérification avant mise à jour souscription chasse ID {$chasse_id} : Utilisateur ID {$user_id}");

    // ✅ Première souscription à une énigme de cette chasse => Marquer la chasse comme souscrite
    update_user_meta($user_id, "souscription_chasse_{$chasse_id}", true);

    // 🔄 Mise à jour du compteur global de souscriptions à la chasse
    $meta_key = "total_joueurs_souscription_chasse_{$chasse_id}";
    $total_souscriptions = get_post_meta($chasse_id, $meta_key, true) ?: 0;
    update_post_meta($chasse_id, $meta_key, $total_souscriptions + 1);
}
/**
 * Vérifie si un utilisateur est engagé dans une chasse.
 *
 * @param int $user_id
 * @param int $chasse_id
 * @return bool
 */
function utilisateur_est_engage_dans_chasse(int $user_id, int $chasse_id): bool
{
    global $wpdb;
    if (!$user_id || !$chasse_id) return false;

    $table = $wpdb->prefix . 'engagements';

    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM $table WHERE user_id = %d AND chasse_id = %d AND enigme_id IS NULL LIMIT 1",
        $user_id,
        $chasse_id
    ));
}



/**
 * 📌 Validation des incohérences de dates dans les chasses.
 */
add_filter('acf/validate_value/name=date_de_fin', function ($valid, $value, $field, $input) {
    if (!$valid) {
        return $valid; // 🚫 Ne pas écraser d'autres erreurs
    }

    if (get_post_type($_POST['post_ID'] ?? 0) !== 'chasse') {
        return $valid;
    }

    // 🔄 Reformater `date_de_fin` si nécessaire
    if (preg_match('/^\d{8}$/', $value)) {
        $value = substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2);
    }

    // 🔍 Récupération de la date de début à partir des données du formulaire
    $caracteristiques_key = 'field_67ca7fd7f5117'; // ID du groupe "caracteristiques"
    $date_debut_key = 'field_67b58c6fd98ec'; // ID du champ "date_de_debut"
    $date_debut = $_POST['acf'][$caracteristiques_key][$date_debut_key] ?? null;


    // ✅ Vérification : La date de fin ne peut pas être avant la date de début
    if (!empty($date_debut) && !empty($value) && strtotime($value) < strtotime($date_debut)) {
        return __('⚠️ Erreur : La date de fin ne peut pas être antérieure à la date de début.', 'chassesautresor-com');
    }

    // ✅ Vérification : Si "maintenant" est sélectionné, date_de_fin ne peut pas être antérieure à aujourd'hui
    if (
        $_POST['acf'][$caracteristiques_key]['field_67ca858935c21'] === 'maintenant' &&
        !empty($value) && strtotime($value) < strtotime(date('Y-m-d'))
    ) {
        return __('⚠️ Erreur : La date de fin ne peut pas être antérieure à la date du jour si la chasse commence maintenant.', 'chassesautresor-com');
    }

    return $valid;
}, 10, 4);

/**
 * 📌 Déclenche les actions nécessaires lorsqu'une chasse est terminée.
 *
 * Met à jour le statut de toutes les énigmes associées pour tous les joueurs
 * dans la table `wp_enigme_statuts_utilisateur` ainsi que dans les metas
 * utilisateur correspondantes.
 *
 * @param int $chasse_id ID de la chasse concernée.
 * @return void
 */
function gerer_chasse_terminee($chasse_id)
{
    if (!$chasse_id) {
        return;
    }

    $enigmes = recuperer_enigmes_associees($chasse_id);
    if (empty($enigmes)) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'enigme_statuts_utilisateur';
    $now   = current_time('mysql');

    $placeholders = implode(',', array_fill(0, count($enigmes), '%d'));
    $sql = "
        SELECT user_id, MIN(date_mise_a_jour) AS first_finish
        FROM {$table}
        WHERE statut IN ('resolue', 'terminee')
          AND enigme_id IN ($placeholders)
        GROUP BY user_id
        HAVING COUNT(DISTINCT enigme_id) = %d
        ORDER BY first_finish ASC
    ";
    $results = $wpdb->get_results(
        $wpdb->prepare($sql, array_merge($enigmes, [count($enigmes)]))
    );

    $winner_ids = wp_list_pluck($results, 'user_id');
    $winner_total = count($winner_ids);
    $max_winners = (int) get_field('chasse_infos_nb_max_gagants', $chasse_id);
    if ($max_winners > 0 && $winner_total > $max_winners) {
        error_log("⚠️ Plus de gagnants ({$winner_total}) que la limite ({$max_winners}) pour la chasse {$chasse_id}. Seuls les premiers arrivés seront retenus.");
        $winner_ids = array_slice($winner_ids, 0, $max_winners);
    }

    $winner_names = [];
    foreach ($winner_ids as $uid) {
        $user = get_userdata((int) $uid);
        if ($user) {
            $winner_names[] = $user->display_name ?: $user->user_login;
        }
    }

    $list = implode(', ', $winner_names);
    update_field('chasse_cache_gagnants', $list, $chasse_id);

    $date = current_time('Y-m-d');
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if ($date_obj && $date_obj->format('Y-m-d') === $date) {
        update_field('chasse_cache_date_decouverte', $date, $chasse_id);
    }

    foreach ($enigmes as $enigme_id) {
        // 🗃️ Mise à jour des statuts en base
        $wpdb->update(
            $table,
            [
                'statut'           => 'terminee',
                'date_mise_a_jour' => $now,
            ],
            [
                'enigme_id' => $enigme_id,
            ],
            ['%s', '%s'],
            ['%d']
        );

        // 🔄 Synchronisation des metas utilisateur
        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$table} WHERE enigme_id = %d",
            $enigme_id
        ));

        foreach ($user_ids as $uid) {
            update_user_meta((int) $uid, "statut_enigme_{$enigme_id}", 'terminee');
        }
    }

    // 🏆 Actions futures : récompenses, notifications, etc.
    //attribuer_recompenses_chasse($chasse_id);
    //notifier_fin_chasse($chasse_id);
}



// ==================================================
// 📦 AFFICHAGE
// ==================================================
/**
 * 🔹 afficher_picture_vignette_chasse() → Affiche une balise <picture> responsive pour l’image d’une chasse.
 * 🔹 afficher_chasse_associee_callback → ffiche les informations principales de la chasse associée à l’énigme.
 */


/**
 *
 * @param int    $chasse_id
 * @param string $alt Texte alternatif pour l’image (optionnel)
 */
function afficher_picture_vignette_chasse($chasse_id, $alt = '')
{
    if (!is_numeric($chasse_id)) return;

    $image = get_field('chasse_principale_image', $chasse_id);
    $permalink = get_permalink($chasse_id);

    if (!is_array($image) || empty($image['url'])) {
        echo '<a href="' . esc_url($permalink) . '" class="image-chasse-placeholder">';
        echo '<i class="fa-solid fa-map fa-2x"></i>';
        echo '</a>';
        return;
    }

    $src_small = $image['sizes']['medium'] ?? $image['url'];
    $src_large = $image['sizes']['large'] ?? $image['url'];
    $alt = esc_attr($alt ?: $image['alt'] ?? get_the_title($chasse_id));

    echo '<a href="' . esc_url($permalink) . '">';
    echo '<picture>';
    echo '<source media="(min-width: 768px)" srcset="' . esc_url($src_large) . '">';
    echo '<img src="' . esc_url($src_small) . '" alt="' . $alt . '" loading="lazy">';
    echo '</picture>';
    echo '</a>';
}



/**
 * 🏴‍☠️ Affiche les informations principales de la chasse associée à l’énigme.
 *
 * Informations affichées (sauf si l'énigme est souscrite/en cours) :
 * - Titre de la chasse
 * - Lot
 * - Durée
 * - Icône Discord cliquable (si lien ACF disponible)
 *
 * @return string HTML des informations de la chasse ou chaîne vide si aucune chasse associée ou énigme en cours.
 */
function afficher_chasse_associee_callback()
{
    if (!is_singular('enigme')) return '';

    $enigme_id = get_the_ID();
    $user_id = get_current_user_id();

    // ✅ Si l’énigme est souscrite (en cours), on n'affiche pas la chasse associée
    $statut = enigme_get_statut_light($user_id, $enigme_id);
    if ($statut === 'en_cours') return '';

    $chasse = recuperer_chasse_associee($enigme_id);
    if (!$chasse) return ''; // 🚫 Pas de chasse associée

    $infos_chasse = recuperer_infos_chasse($chasse->ID) ?: [
        'lot' => 'Non spécifié',
        'date_de_debut' => 'Non spécifiée',
        'date_de_fin' => 'Non spécifiée',
    ];

    $lien_discord = get_field('lien_discord', $chasse->ID);
    $icone_discord = esc_url(get_stylesheet_directory_uri() . '/assets/images/discord-icon.png');
    $titre = esc_html(get_the_title($chasse->ID));
    $url = esc_url(get_permalink($chasse->ID));

    ob_start(); ?>
    <section class="chasse-associee">
        <h3>Chasse au Trésor</h3>
        <h2><strong><a href="<?= $url; ?>" class="lien-chasse-associee"><?= $titre; ?></a></strong></h2>
        <p>🏆 <strong>Lot :</strong> <?= esc_html($infos_chasse['lot']); ?></p>
        <p>📅 <strong>Durée :</strong> <?= esc_html($infos_chasse['date_de_debut']); ?> au <?= esc_html($infos_chasse['date_de_fin']); ?></p>

        <?php if (!empty($lien_discord)) : ?>
            <p>
                <a href="<?= esc_url($lien_discord); ?>" target="_blank" rel="noopener noreferrer" aria-label="Rejoindre le Discord">
                    <img src="<?= $icone_discord; ?>" alt="Discord" class="discord-icon">
                </a>
            </p>
        <?php endif; ?>
    </section>
<?php
    return ob_get_clean();
}

/**
 * Détermine si l'organisateur peut demander la validation d'une chasse.
 *
 * @param int $chasse_id ID de la chasse.
 * @param int $user_id   ID de l'utilisateur.
 * @return bool
 */
function peut_valider_chasse(int $chasse_id, int $user_id): bool
{
    if (!$chasse_id || !$user_id) {
        return false;
    }

    if (get_post_type($chasse_id) !== 'chasse') {
        return false;
    }

    if (!est_organisateur($user_id)) {
        return false;
    }

    if (!utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)) {
        return false;
    }

    $organisateur_id = get_organisateur_from_chasse($chasse_id);
    if (!$organisateur_id || !get_field('organisateur_cache_complet', $organisateur_id)) {
        return false;
    }

    if (!get_field('chasse_cache_complet', $chasse_id)) {
        return false;
    }

    if (get_post_status($chasse_id) !== 'pending') {
        return false;
    }

    $statut_validation = get_field('chasse_cache_statut_validation', $chasse_id);
    $statut_metier     = get_field('chasse_cache_statut', $chasse_id);

    if (!in_array($statut_validation ?? '', ['creation', 'correction'], true)) {
        return false;
    }

    if (($statut_metier ?? '') !== 'revision') {
        return false;
    }

    // Utilise la requête directe pour lister toutes les énigmes rattachées
    // afin d'éviter toute incohérence liée au cache "chasse_cache_enigmes".
    $enigmes = recuperer_ids_enigmes_pour_chasse($chasse_id);
    if (empty($enigmes)) {
        return false;
    }

    foreach ($enigmes as $eid) {
        $etat = get_field('enigme_cache_etat_systeme', $eid);
        $complet = get_field('enigme_cache_complet', $eid);
        if ($etat !== 'bloquee_chasse' || !$complet) {
            return false;
        }
    }

    return true;
}

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
    if (!$user_id) {
        return [
            'cta_html'    => '<a href="' . esc_url(site_url('/mon-compte')) . '" class="bouton-cta">S\'identifier</a>',
            'cta_message' => 'Vous devez être identifié pour participer à cette chasse',
            'type'        => 'connexion',
        ];
    }

    // 🔐 Admin ou organisateur : pas de bouton
    if (current_user_can('administrator') || utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)) {
        return ['cta_html' => '', 'cta_message' => '', 'type' => ''];
    }

    // ✅ Déjà engagé
    if (utilisateur_est_engage_dans_chasse($user_id, $chasse_id)) {
        return ['cta_html' => '', 'cta_message' => '', 'type' => 'engage'];
    }

    // ❌ Chasse non validée
    if ($validation !== 'valide') {
        return ['cta_html' => '', 'cta_message' => '', 'type' => ''];
    }

    $html    = '';
    $message = '';
    $type    = '';

    if ($statut === 'a_venir') {
        $html = '<button class="bouton-cta" disabled>Indisponible</button>';
        $type = 'indisponible';
        $message = $date_debut
            ? 'Chasse disponible à partir du ' . date_i18n('d/m/Y \à H:i', strtotime($date_debut))
            : 'Chasse disponible prochainement';
    } elseif ($statut === 'en_cours' || $statut === 'payante') {
        $cout_points        = (int) get_field('chasse_infos_cout_points', $chasse_id);
        $points_disponibles = get_user_points($user_id);

        if ($statut === 'payante' && $cout_points > 0 && $points_disponibles < $cout_points) {
            $html = '<button class="bouton-cta" disabled>Points insuffisants</button>';
            $points_manquants = $cout_points - $points_disponibles;
            $message = sprintf(
                'Il vous manque %d points pour participer à cette chasse.',
                $points_manquants
            );
            $type = 'indisponible';
        } else {
            // 🔓 Participation gratuite à ce stade, engagement simple
            $html  = '<form method="post" action="' . esc_url(site_url('/traitement-engagement')) . '" class="cta-chasse-form">';
            $html .= '<input type="hidden" name="chasse_id" value="' . esc_attr($chasse_id) . '">';
            $html .= wp_nonce_field('engager_chasse_' . $chasse_id, 'engager_chasse_nonce', true, false);
            $html .= '<button type="submit" class="bouton-cta">Participer</button>';
            $html .= '</form>';
            $message = 'Accès libre à cette chasse. Les tentatives seront tarifées individuellement.';
            $type    = 'engager';
        }
    } elseif ($statut === 'termine') {
        // ✅ Chasse terminée : engagement gratuit et automatique
        $html  = '<form method="post" action="' . esc_url(site_url('/traitement-engagement')) . '" class="cta-chasse-form">';
        $html .= '<input type="hidden" name="chasse_id" value="' . esc_attr($chasse_id) . '">';
        $html .= wp_nonce_field('engager_chasse_' . $chasse_id, 'engager_chasse_nonce', true, false);
        $html .= '<button type="submit" class="bouton-cta">Voir</button>';
        $html .= '</form>';
        $type = 'voir';
        $message = $date_fin
            ? 'Cette chasse est terminée depuis le ' . date_i18n('d/m/Y', strtotime($date_fin))
            : 'Cette chasse est terminée';
    }

    return [
        'cta_html'    => $html,
        'cta_message' => $message,
        'type'        => $type,
    ];
}

/**
 * Compte le nombre de joueurs ayant engagé au moins une énigme de la chasse.
 *
 * @param int $chasse_id ID de la chasse.
 * @return int Nombre de joueurs uniques engagés.
 */
function compter_joueurs_engages_chasse(int $chasse_id): int
{
    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        return 0;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'engagements';

    $query = $wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id)
         FROM $table
         WHERE chasse_id = %d",
        $chasse_id
    );

    return (int) $wpdb->get_var($query);
}


/**
 * Enregistre un engagement à une chasse pour un utilisateur.
 *
 * @param int $user_id
 * @param int $chasse_id
 * @return bool true si un enregistrement a été effectué, false sinon.
 */
function enregistrer_engagement_chasse(int $user_id, int $chasse_id): bool
{
    global $wpdb;

    if (!$user_id || !$chasse_id) {
        return false;
    }

    if (current_user_can('administrator') || utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)) {
        return false;
    }

    $table = $wpdb->prefix . 'engagements';

    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM $table WHERE user_id = %d AND chasse_id = %d AND enigme_id IS NULL LIMIT 1",
        $user_id,
        $chasse_id
    ));

    if ($exists) {
        return false;
    }

    $inserted = $wpdb->insert(
        $table,
        [
            'user_id'         => $user_id,
            'chasse_id'       => $chasse_id,
            'enigme_id'       => null,
            'date_engagement' => current_time('mysql', 1),
        ],
        ['%d', '%d', '%s', '%s']
    );

    return (bool) $inserted;
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
        _n('%d joueur', '%d joueurs', $quantite, 'chassesautresor'),
        $nombre
    );
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

    $query = get_chasses_de_organisateur($organisateur_id);
    $chasses = is_a($query, 'WP_Query') ? $query->posts : (array) $query;

    foreach ($chasses as $post) {
        if (peut_valider_chasse($post->ID, $user_id)) {
            return $post->ID;
        }
    }

    return null;
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
        <button type="submit" class="bouton-cta bouton-validation-chasse">VALIDATION</button>
    </form>
<?php
    return ob_get_clean();
}

/**
 * Affiche un message relatif à la validation d'une chasse.
 *
 * - Après l'envoi de la demande via ?validation_demandee=1,
 *   un message de succès est affiché une seule fois.
 * - Tant que le statut reste "en_attente", un message
 *   d'information indique que la demande est en cours.
 *
 * @param int $chasse_id ID de la chasse.
 * @return void
 */
function afficher_message_validation_chasse(int $chasse_id): void
{
    $validation_envoyee = !empty($_GET['validation_demandee']);
    $statut_validation  = get_field('chasse_cache_statut_validation', $chasse_id);

    if ($validation_envoyee) {
        echo '<p class="message-succes">✅ Votre demande de validation est en cours de traitement par l’équipe.</p>';
        echo '<script>if(window.history.replaceState){const u=new URL(window.location);u.searchParams.delete("validation_demandee");history.replaceState(null,"",u);}</script>';
    } elseif ($statut_validation === 'en_attente' && !current_user_can('administrator')) {
        echo '<p class="message-info">⏳ Votre demande est en cours de traitement</p>';
    }
}


/**
 * Vérifie si la solution d'une énigme peut être affichée.
 *
 * La solution n'est visible que si la chasse associée est terminée
 * et que l'éventuel délai configuré est écoulé.
 *
 * @param int $enigme_id ID de l'énigme.
 * @return bool
 */
function solution_peut_etre_affichee(int $enigme_id): bool
{
    if (!$enigme_id || get_post_type($enigme_id) !== 'enigme') {
        return false;
    }

    $chasse_id = recuperer_id_chasse_associee($enigme_id);
    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        return false;
    }

    $statut   = get_field('statut_chasse', $chasse_id);
    $terminee = is_string($statut) && in_array(strtolower($statut), ['terminée', 'termine', 'terminé'], true);
    if (!$terminee) {
        return false;
    }

    $mode  = get_field('enigme_solution_mode', $enigme_id) ?: 'fin_de_chasse';
    $delai = (int) get_field('enigme_solution_delai', $enigme_id);
    $heure = get_field('enigme_solution_heure', $enigme_id);
    $date  = get_field('enigme_solution_date', $enigme_id);

    $now = current_time('timestamp');

    if ($mode === 'delai_fin_chasse') {
        $base = get_field('date_de_decouverte', $chasse_id);
        if (!$base) {
            $base = get_field('chasse_infos_date_fin', $chasse_id);
        }
        $timestamp_base = $base ? strtotime($base) : $now;
        $cible = strtotime("+$delai days $heure", $timestamp_base);
        if ($cible && $now < $cible) {
            return false;
        }
    } elseif ($mode === 'date_fin_chasse') {
        $cible = $date ? strtotime("$date $heure") : null;
        if ($cible && $now < $cible) {
            return false;
        }
    }

    return true;
}

/**
 * Prépare les informations d'affichage pour une carte de chasse.
 *
 * @param int $chasse_id ID de la chasse.
 * @return array Tableau associatif prêt pour le template.
 */
function preparer_infos_affichage_carte_chasse(int $chasse_id): array
{
    if (get_post_type($chasse_id) !== 'chasse') {
        return [];
    }

    $titre     = get_the_title($chasse_id);
    $permalink = get_permalink($chasse_id);

    $description   = get_field('chasse_principale_description', $chasse_id);
    $texte_complet = wp_strip_all_tags($description);
    $extrait       = wp_trim_words($texte_complet, 60, '...');

    $image_data = get_field('chasse_principale_image', $chasse_id);
    $image = '';
    if (is_array($image_data) && !empty($image_data['sizes']['medium'])) {
        $image = $image_data['sizes']['medium'];
    } elseif ($image_data) {
        $image_id = is_array($image_data) ? ($image_data['ID'] ?? 0) : (int) $image_data;
        $image = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    }
    if (!$image) {
        $image = get_the_post_thumbnail_url($chasse_id, 'medium');
    }

    $champs = chasse_get_champs($chasse_id);
    $titre_recompense  = $champs['titre_recompense'];
    $valeur_recompense = $champs['valeur_recompense'];
    $cout_points       = (int) $champs['cout_points'];
    $date_debut        = $champs['date_debut'];
    $date_fin          = $champs['date_fin'];
    $illimitee         = $champs['illimitee'];

    $date_debut_affichage = formater_date($date_debut);
    $date_fin_affichage   = $illimitee ? 'Illimitée' : ($date_fin ? formater_date($date_fin) : 'Non spécifiée');

    $nb_joueurs       = compter_joueurs_engages_chasse($chasse_id);
    $nb_joueurs_label = formater_nombre_joueurs($nb_joueurs);

    verifier_ou_recalculer_statut_chasse($chasse_id);
    $statut            = get_field('chasse_cache_statut', $chasse_id) ?: 'revision';
    $statut_validation = get_field('chasse_cache_statut_validation', $chasse_id);
    $statut_label      = ucfirst(str_replace('_', ' ', $statut));
    $badge_class       = 'statut-' . $statut;

    if ($statut === 'revision') {
        if ($statut_validation === 'creation') {
            $statut_label = 'création';
        } elseif ($statut_validation === 'correction') {
            $statut_label = 'correction';
        } elseif ($statut_validation === 'en_attente') {
            $statut_label = 'en attente';
        }
    } elseif ($statut === 'payante') {
        $statut_label = 'en cours';
        $badge_class   = 'statut-en_cours';
    }

    $enigmes_associees = recuperer_enigmes_associees($chasse_id);
    $total_enigmes     = count($enigmes_associees);

    $user_id = get_current_user_id();
    $cta_data = generer_cta_chasse($chasse_id, $user_id);

    $liens = get_field('chasse_principale_liens', $chasse_id);
    $liens = is_array($liens) ? $liens : [];
    if (empty($liens)) {
        $orga_id   = get_organisateur_from_chasse($chasse_id);
        $liens_org = organisateur_get_liens_actifs($orga_id);
        foreach ($liens_org as $type => $url) {
            $liens[] = [
                'chasse_principale_liens_type' => $type,
                'chasse_principale_liens_url'  => $url,
            ];
        }
    }
    $has_lien = false;
    foreach ($liens as $entree) {
        $type_raw = $entree['chasse_principale_liens_type'] ?? null;
        $url      = $entree['chasse_principale_liens_url'] ?? null;
        $type     = is_array($type_raw) ? ($type_raw[0] ?? '') : $type_raw;
        if (is_string($type) && trim($type) !== '' && is_string($url) && trim($url) !== '') {
            $has_lien = true;
            break;
        }
    }
    $liens_html = $has_lien ? render_liens_publics($liens, 'chasse') : '';

    $footer_icones = [];
    if ($cout_points > 0) {
        $footer_icones[] = 'coins-points';
    }

    $modes = [];
    foreach ($enigmes_associees as $eid) {
        $mode = get_field('enigme_mode_validation', $eid);
        if ($mode) {
            $modes[$mode] = true;
        }
    }
    if (isset($modes['manuelle'])) {
        $footer_icones[] = 'reply-mail';
    } elseif (isset($modes['automatique'])) {
        $footer_icones[] = 'reply-auto';
    }

    $lot_html = '';
    if (!empty($titre_recompense) && (float) $valeur_recompense > 0) {
        $footer_icones[] = 'trophy';
        $lot_html = '<div class="chasse-lot" aria-live="polite">'
            . '<strong>Récompense :</strong> '
            . esc_html($titre_recompense) . ' — ' . esc_html($valeur_recompense) . ' €'
            . '</div>';
    }


    $extrait_html = $extrait ? '<p class="chasse-intro-extrait liste-elegante"> <strong>Présentation :</strong> ' . esc_html($extrait) . '</p>' : '';

    $cta_html    = $cta_data['cta_html'] ?? '';
    $cta_message = $cta_data['cta_message'] ?? '';

    $footer_liens_html = '';
    $footer_icones_html = '';

    if ($has_lien) {
        $footer_liens_html = '<div class="liens-publics-carte">' . $liens_html . '</div>';
    }

    if (!empty($footer_icones)) {
        $footer_icones_html = '<div class="footer-icones">';
        foreach ($footer_icones as $icn) {
            $footer_icones_html .= get_svg_icon($icn);
        }
        $footer_icones_html .= '</div>';
    }

    $footer_html = '';
    if ($footer_liens_html || $footer_icones_html) {
        $footer_html = '<div class="carte-ligne__footer meta-etiquette">'
            . $footer_icones_html
            . $footer_liens_html
            . '</div>';
    }


    return [
        'titre'             => $titre,
        'permalink'         => $permalink,
        'image'             => $image,
        'total_enigmes'     => $total_enigmes,
        'nb_joueurs_label'  => $nb_joueurs_label,
        'date_debut'        => $date_debut_affichage,
        'date_fin'          => $date_fin_affichage,
        'badge_class'       => $badge_class,
        'statut_label'      => $statut_label,
        'classe_statut'     => $badge_class,
        'extrait_html'      => $extrait_html,
        'lot_html'          => $lot_html,
        'cta_html'          => $cta_html,
        'cta_message'       => $cta_message,
        'cta_type'         => $cta_data['type'] ?? '',
        'footer_html'       => $footer_html,
    ];
}

/**
 * Prépare les informations complètes d'affichage pour une chasse.
 * Cette fonction centralise tous les appels ACF et fonctions métiers
 * afin d'éviter les appels répétés lors du rendu d'une page.
 *
 * @param int      $chasse_id ID de la chasse.
 * @param int|null $user_id   Utilisateur courant pour le CTA. Par défaut get_current_user_id().
 * @return array
 */
function preparer_infos_affichage_chasse(int $chasse_id, ?int $user_id = null): array
{
    static $memo = [];

    $user_id = $user_id ?? get_current_user_id();
    $cache_key = $chasse_id . '-' . $user_id;

    if (isset($memo[$cache_key])) {
        return $memo[$cache_key];
    }

    if (get_post_type($chasse_id) !== 'chasse') {
        return [];
    }

    $champs = chasse_get_champs($chasse_id);

    $description   = get_field('chasse_principale_description', $chasse_id);
    $texte_complet = wp_strip_all_tags($description);
    $extrait       = wp_trim_words($texte_complet, 60, '...');

    $image_raw = get_field('chasse_principale_image', $chasse_id);
    $image_id  = is_array($image_raw) ? ($image_raw['ID'] ?? null) : $image_raw;
    $image_url = $image_id ? wp_get_attachment_image_src($image_id, 'large')[0] : null;

    $liens = get_field('chasse_principale_liens', $chasse_id);
    $liens = is_array($liens) ? $liens : [];

    $enigmes = recuperer_enigmes_associees($chasse_id);

    $memo[$cache_key] = [
        'champs'            => $champs,
        'description'       => $description,
        'texte_complet'     => $texte_complet,
        'extrait'           => $extrait,
        'image_raw'         => $image_raw,
        'image_id'          => $image_id,
        'image_url'         => $image_url,
        'liens'             => $liens,
        'enigmes_associees' => $enigmes,
        'total_enigmes'     => count($enigmes),
        'cta_data'          => generer_cta_chasse($chasse_id, $user_id),
        'nb_joueurs'        => compter_joueurs_engages_chasse($chasse_id),
        'statut'            => get_field('chasse_cache_statut', $chasse_id) ?: 'revision',
        'statut_validation' => get_field('chasse_cache_statut_validation', $chasse_id),
    ];

    return $memo[$cache_key];
}
