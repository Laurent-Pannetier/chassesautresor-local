<?php
defined('ABSPATH') || exit;


    // ==================================================
    // 📬 GESTION DES RÉPONSES MANUELLES (FRONTEND)
    // ==================================================

    // 🔹 afficher_formulaire_reponse_manuelle() → Affiche le formulaire de réponse manuelle (frontend).
    // 🔹 utilisateur_peut_repondre_manuelle() → Vérifie si l'utilisateur peut répondre à une énigme manuelle.
    // 🔹 soumettre_reponse_manuelle() → Traite la soumission d'une réponse manuelle (frontend).



    /**
     * Affiche le formulaire de réponse manuelle pour une énigme.
     *
     * @param int $enigme_id L'ID de l'énigme.
     * @return string HTML du formulaire.
     */
    function afficher_formulaire_reponse_manuelle($enigme_id)
    {
        if (!is_user_logged_in()) {
            return '<p>Veuillez vous connecter pour répondre à cette énigme.</p>';
        }

        $user_id = get_current_user_id();

        if (!utilisateur_peut_repondre_manuelle($user_id, $enigme_id)) {
            return '<p>Vous ne pouvez plus répondre à cette énigme.</p>';
        }

        $data = calculer_contexte_points($user_id, $enigme_id);
        $nonce = wp_create_nonce('reponse_manuelle_nonce');
        ob_start();
    ?>
    <form method="post" class="bloc-reponse formulaire-reponse-manuelle">
        <label for="reponse_manuelle_<?php echo esc_attr($enigme_id); ?>">Votre réponse :</label>
        <?php if ($data['points_manquants'] > 0) : ?>
            <p class="message-limite" data-points="manquants">
                <?php echo esc_html(sprintf(__('Il vous manque %d points pour soumettre votre réponse.', 'chassesautresor-com'), $data['points_manquants'])); ?>
                <a href="<?php echo esc_url($data['boutique_url']); ?>" class="points-link points-boutique-icon" title="Accéder à la boutique">
                    <span class="points-plus-circle">+</span>
                </a>
            </p>
        <?php else : ?>
            <textarea name="reponse_manuelle" id="reponse_manuelle_<?php echo esc_attr($enigme_id); ?>" rows="3" required></textarea>
        <?php endif; ?>
        <input type="hidden" name="enigme_id" value="<?php echo esc_attr($enigme_id); ?>">
        <input type="hidden" name="reponse_manuelle_nonce" value="<?php echo esc_attr($nonce); ?>">
        <div class="reponse-cta-row">
            <button type="submit" class="bouton-cta" <?php echo $data['disabled']; ?>>Envoyer</button>
            <?php if ($data['cout'] > 0) : ?>
                <span class="badge-cout"><?php echo esc_html($data['cout']); ?> pts</span>
            <?php endif; ?>
        </div>
    </form>
    <div class="reponse-feedback" style="display:none"></div>
    <?php
        return ob_get_clean();
    }

    add_shortcode('formulaire_reponse_manuelle', function ($atts) {
        $atts = shortcode_atts(['id' => null], $atts);
        return afficher_formulaire_reponse_manuelle($atts['id']);
    });

    /**
     * Vérifie si un utilisateur peut soumettre une réponse manuelle à une énigme.
     *
     * @param int $user_id
     * @param int $enigme_id
     * @return bool
     */
function utilisateur_peut_repondre_manuelle(int $user_id, int $enigme_id): bool
{
    if (!$user_id || !$enigme_id) return false;

        $statut = enigme_get_statut_utilisateur($enigme_id, $user_id);

        // Autoriser uniquement les statuts actifs
        $autorisés = ['en_cours', 'echouee', 'abandonnee'];

    return in_array($statut, $autorisés, true);
}

/**
 * Calcule les informations de coût et de points pour le joueur.
 */
function calculer_contexte_points(int $user_id, int $enigme_id): array
{
    $cout = (int) get_field('enigme_tentative_cout_points', $enigme_id);
    $solde = get_user_points($user_id);
    $points_manquants = max(0, $cout - $solde);

    return [
        'cout'            => $cout,
        'boutique_url'    => esc_url(home_url('/boutique/')),
        'disabled'        => $points_manquants > 0 ? 'disabled' : '',
        'points_manquants' => $points_manquants,
    ];
}


    /**
     * Intercepte et traite la soumission d'une réponse manuelle à une énigme (frontend).
     *
     * Conditions :
     * - utilisateur connecté
     * - champ réponse + nonce + enigme_id présents
     * - nonce valide
     */
function soumettre_reponse_manuelle()
{
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $user_id   = get_current_user_id();
    $enigme_id = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
    $reponse   = isset($_POST['reponse_manuelle']) ? sanitize_textarea_field($_POST['reponse_manuelle']) : '';
    $nonce     = $_POST['reponse_manuelle_nonce'] ?? '';

    if (!$enigme_id || $reponse === '' || !wp_verify_nonce($nonce, 'reponse_manuelle_nonce')) {
        wp_send_json_error('invalide');
    }

    if (!utilisateur_peut_repondre_manuelle($user_id, $enigme_id)) {
        wp_send_json_error('interdit');
    }

    $current_statut = $wpdb->get_var($wpdb->prepare(
        "SELECT statut FROM {$wpdb->prefix}enigme_statuts_utilisateur WHERE user_id = %d AND enigme_id = %d",
        $user_id,
        $enigme_id
    ));

    if (in_array($current_statut, ['resolue', 'terminee'], true)) {
        wp_send_json_error('deja_resolue');
    }

    $cout = (int) get_field('enigme_tentative_cout_points', $enigme_id);
    if ($cout > get_user_points($user_id)) {
        wp_send_json_error('points_insuffisants');
    }

    if ($cout > 0) {
        $reason = sprintf("Tentative de réponse pour l'énigme #%d", $enigme_id);
        deduire_points_utilisateur($user_id, $cout, $reason, 'tentative', $enigme_id);
    }

    $uid = inserer_tentative($user_id, $enigme_id, $reponse);
    enigme_mettre_a_jour_statut_utilisateur($enigme_id, $user_id, 'soumis', true);

    envoyer_mail_reponse_manuelle($user_id, $enigme_id, $reponse, $uid);

    $solde = get_user_points($user_id);

    wp_send_json_success([
        'uid'    => $uid,
        'points' => $solde,
    ]);
}
add_action('wp_ajax_soumettre_reponse_manuelle', 'soumettre_reponse_manuelle');
add_action('wp_ajax_nopriv_soumettre_reponse_manuelle', 'soumettre_reponse_manuelle');

/**
 * Traite la soumission d'une réponse automatique via AJAX.
 */
function soumettre_reponse_automatique()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $user_id = get_current_user_id();
    $enigme_id = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
    $reponse = isset($_POST['reponse']) ? sanitize_text_field($_POST['reponse']) : '';
    $nonce   = $_POST['nonce'] ?? '';

    if (!$enigme_id || $reponse === '' || !wp_verify_nonce($nonce, 'reponse_auto_nonce')) {
        wp_send_json_error('invalide');
    }

    $etat = enigme_get_etat_systeme($enigme_id);
    $statut = enigme_get_statut_utilisateur($enigme_id, $user_id);

    $autorisations = ['non_commencee', 'en_cours', 'abandonnee', 'echouee'];
    if ($etat !== 'accessible' || !in_array($statut, $autorisations, true)) {
        wp_send_json_error('interdit');
    }

    $max = (int) get_field('enigme_tentative_max', $enigme_id);
    $deja = compter_tentatives_du_jour($user_id, $enigme_id);
    if ($max && $deja >= $max) {
        wp_send_json_error('tentatives_epuisees');
    }

    $cout = (int) get_field('enigme_tentative_cout_points', $enigme_id);
    if ($cout > get_user_points($user_id)) {
        wp_send_json_error('points_insuffisants');
    }

    $reponse_attendue = trim((string) get_field('enigme_reponse_bonne', $enigme_id));
    $respecter_casse  = (int) get_field('enigme_reponse_casse', $enigme_id) === 1;

    $saisie_brute = trim($reponse);
    $saisie_cmp_main = $respecter_casse ? $saisie_brute : mb_strtolower($saisie_brute);
    $attendue_cmp = $respecter_casse ? $reponse_attendue : mb_strtolower($reponse_attendue);

    $resultat = $saisie_cmp_main === $attendue_cmp ? 'bon' : 'faux';
    $message  = '';
    $index    = 0;

    if ($resultat === 'faux') {
        $variantes = [];
        for ($i = 1; $i <= 4; $i++) {
            $txt   = trim((string) get_field("texte_{$i}", $enigme_id));
            $msg   = trim((string) get_field("message_{$i}", $enigme_id));
            $casse = (int) get_field("respecter_casse_{$i}", $enigme_id) === 1;

            if ($txt !== '') {
                $variantes[$i] = [
                    'texte'   => $txt,
                    'message' => $msg,
                    'casse'   => $casse,
                ];
            }
        }

        foreach ($variantes as $i => $var) {
            $cmp_saisie = $var['casse'] ? $saisie_brute : mb_strtolower($saisie_brute);
            $cmp_txt    = $var['casse'] ? $var['texte'] : mb_strtolower($var['texte']);

            if ($cmp_saisie === $cmp_txt) {
                $resultat = 'variante';
                $message  = $var['message'];
                $index    = $i;
                break;
            }
        }
    }

    $lock_key = "enigme_lock_{$enigme_id}_{$user_id}";
    if (!wp_cache_add($lock_key, 1, 'enigme', 15)) {
        wp_send_json_error('doublon');
    }

    try {
        $uid = traiter_tentative($user_id, $enigme_id, $reponse, $resultat, true, false, false);
    } catch (Throwable $e) {
        wp_cache_delete($lock_key, 'enigme');
        cat_debug('Erreur tentative : ' . $e->getMessage());
        wp_send_json_error('erreur_interne');
    }

    wp_cache_delete($lock_key, 'enigme');

    $compteur = compter_tentatives_du_jour($user_id, $enigme_id);
    $solde = get_user_points($user_id);

    wp_send_json_success([
        'resultat' => $resultat,
        'message'  => $message,
        'uid'      => $uid,
        'compteur' => $compteur,
        'points'   => $solde,
    ]);
}
add_action('wp_ajax_soumettre_reponse_automatique', 'soumettre_reponse_automatique');
add_action('wp_ajax_nopriv_soumettre_reponse_automatique', 'soumettre_reponse_automatique');



    // ==================================================
    // ✉️ ENVOI D'EMAILS (RÉPONSES MANUELLES)
    // ==================================================

    // 🔹 envoyer_mail_reponse_manuelle() → Envoie un mail HTML à l'organisateur avec la réponse (expéditeur = joueur).
    // 🔹 envoyer_mail_resultat_joueur() → Envoie un mail HTML au joueur après validation ou refus de sa réponse.
    // 🔹 envoyer_mail_accuse_reception_joueur() → Envoie un accusé de réception au joueur juste après sa soumission.

    /**
     * Envoie un email à l'organisateur avec la réponse manuelle soumise.
     *
     * @param int    $user_id
     * @param int    $enigme_id
     * @param string $reponse
     * @param string $uid
     */
    function envoyer_mail_reponse_manuelle($user_id, $enigme_id, $reponse, $uid)
    {
        // 🔍 Email organisateur
        $chasse  = get_field('enigme_chasse_associee', $enigme_id, false);
        if (is_array($chasse)) {
            $chasse_id = is_object($chasse[0]) ? (int) $chasse[0]->ID : (int) $chasse[0];
        } elseif (is_object($chasse)) {
            $chasse_id = (int) $chasse->ID;
        } else {
            $chasse_id = (int) $chasse;
        }

        $organisateur_id = $chasse_id ? get_organisateur_from_chasse($chasse_id) : null;
        $email_organisateur = $organisateur_id ? get_field('email_organisateur', $organisateur_id) : '';
        if (!$email_organisateur) {
            $email_organisateur = get_option('admin_email');
        }

        $titre_enigme = html_entity_decode(get_the_title($enigme_id), ENT_QUOTES, 'UTF-8');
        $user = get_userdata($user_id);
        $subject_raw = '[Réponse Énigme] ' . $titre_enigme;

        $subject = function_exists('wp_encode_mime_header')
            ? wp_encode_mime_header($subject_raw)
            : mb_encode_mimeheader($subject_raw, 'UTF-8', 'B', "\r\n");

        $date        = date_i18n('j F Y à H:i', current_time('timestamp'));
        $url_enigme  = get_permalink($enigme_id);
        $profil_url  = get_author_posts_url($user_id);
        $traitement_url = esc_url(add_query_arg([
            'uid' => $uid,
        ], home_url('/traitement-tentative')));

        // 📧 Message HTML
        $message  = '<div style="font-family:Arial,sans-serif; font-size:14px;">';
        $message .= '<p>Une nouvelle réponse manuelle a été soumise par <strong><a href="' . esc_url($profil_url) . '" target="_blank">' . esc_html($user->user_login) . '</a></strong>.</p>';
        $message .= '<p><strong>🧩 Énigme :</strong> <em>' . esc_html($titre_enigme) . '</em></p>';
        $message .= '<p><strong>📝 Réponse :</strong><br><blockquote>' . nl2br(esc_html($reponse)) . '</blockquote></p>';
        $message .= '<p><strong>📅 Soumise le :</strong> ' . esc_html($date) . '</p>';
        $message .= '<p><strong>🔐 Identifiant :</strong> ' . esc_html($uid) . '</p>';
        $message .= '<hr>';
        $message .= '<p style="text-align:center;">';
        $message .= '<a href="' . $traitement_url . '" style="background:#0073aa;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">🛠️ Traiter cette tentative</a>';
        $message .= '</p>';
        $message .= '<p><strong>✉️ Contacter le joueur :</strong><br>';
        $message .= '<a href="mailto:' . esc_attr($user->user_email) . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</a></p>';
        $message .= '<p><a href="' . esc_url($url_enigme) . '" target="_blank" style="font-size:0.9em;">🔗 Voir l’énigme en ligne</a></p>';
        $message .= '</div>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $user->display_name . ' <' . $user->user_email . '>',
        ];

        $from_filter = static function ($name) use ($user) {
            return $user->display_name;
        };
        add_filter('wp_mail_from_name', $from_filter, 10, 1);

        wp_mail($email_organisateur, $subject, $message, $headers);
        remove_filter('wp_mail_from_name', $from_filter, 10);
    }


    /**
     * Envoie un email de notification au joueur concernant le résultat de sa réponse à une énigme.
     *
     * @param int    $user_id    L'identifiant de l'utilisateur à notifier.
     * @param int    $enigme_id  L'identifiant de l'énigme concernée.
     * @param string $resultat   Le résultat de la réponse ('bon' pour validée, autre pour refusée).
     *
     * @return void
     */
    function envoyer_mail_resultat_joueur($user_id, $enigme_id, $resultat)
    {
        $user = get_userdata($user_id);
        if (!$user || !is_email($user->user_email)) return;

        $titre_enigme = get_the_title($enigme_id);
        if (!is_string($titre_enigme)) $titre_enigme = '';

        $resultat_txt = $resultat === 'bon' ? 'validée ✅' : 'refusée ❌';
        $sujet = '[Chasses au Trésor] Votre réponse a été ' . $resultat_txt;

        $message  = '<div style="font-family:Arial,sans-serif; font-size:14px;">';
        $message .= '<p>Bonjour <strong>' . esc_html($user->display_name) . '</strong>,</p>';
        $message .= '<p>Votre réponse à l’énigme <strong>« ' . esc_html($titre_enigme) . ' »</strong> a été <strong>' . $resultat_txt . '</strong>.</p>';
        $message .= '<p>Merci pour votre participation !</p>';
        $message .= '<hr>';
        $message .= '<p>🔗 <a href="https://chassesautresor.com/mon-compte" target="_blank">Voir mes réponses</a></p>';
        $message .= '<p style="margin-top:2em;">L’équipe chassesautresor.com</p>';
        $message .= '</div>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        // Sécurisation du champ ACF enigme_chasse_associee
        $chasse_raw = get_field('enigme_chasse_associee', $enigme_id, false);
        if (is_array($chasse_raw)) {
            $first = reset($chasse_raw);
            $chasse_id = is_object($first) ? (int) $first->ID : (int) $first;
        } elseif (is_object($chasse_raw)) {
            $chasse_id = (int) $chasse_raw->ID;
        } elseif (is_numeric($chasse_raw)) {
            $chasse_id = (int) $chasse_raw;
        } else {
            $chasse_id = 0;
        }

        $organisateur_id = get_organisateur_from_chasse($chasse_id);
        $email_organisateur = get_field('email_organisateur', $organisateur_id);

        if (is_array($email_organisateur)) {
            $email_organisateur = reset($email_organisateur);
        }

        if (!is_string($email_organisateur) || !is_email($email_organisateur)) {
            $email_organisateur = get_option('admin_email');
        }

        $headers[] = 'Reply-To: ' . $email_organisateur;

        $from_filter = static function ($name) {
            return 'Chasses au Trésor';
        };
        add_filter('wp_mail_from_name', $from_filter, 10, 1);

        wp_mail($user->user_email, $sujet, $message, $headers);
        remove_filter('wp_mail_from_name', $from_filter, 10); // si mis ailleurs
    }

    /**
     * Envoie un accusé de réception au joueur juste après sa soumission.
     *
     * @param int $user_id
     * @param int $enigme_id
     * @return void
     */
function envoyer_mail_accuse_reception_joueur($user_id, $enigme_id, $uid)
{
        $user = get_userdata($user_id);
        if (!$user || !is_email($user->user_email)) return;

        $titre_enigme = get_the_title($enigme_id);
        $sujet = '[Chasses au Trésor] Tentative de réponse bien reçue pour : ' . html_entity_decode($titre_enigme, ENT_QUOTES, 'UTF-8');

        $message  = '<div style="font-family:Arial,sans-serif; font-size:14px;">';
        $message .= '<p>Bonjour <strong>' . esc_html($user->display_name) . '</strong>,</p>';
        $message .= '<p>Nous avons bien reçu votre tentative de réponse à l’énigme « <strong>' . esc_html($titre_enigme) . '</strong> ».<br>';
        $message .= 'Votre identifiant de tentative est : <code>' . esc_html($uid) . '</code>.</p>';
        $message .= '<p>Elle sera examinée prochainement par l’organisateur.</p>';
        $message .= '<p>Vous recevrez une notification lorsqu’une décision sera prise.</p>';
        $message .= '<hr>';
        $message .= '<p>🔗 <a href="https://chassesautresor.com/mon-compte" target="_blank">Accéder à votre compte</a></p>';
        $message .= '<p style="margin-top:2em;">Merci pour votre participation,<br>L’équipe chassesautresor.com</p>';
        $message .= '</div>';

        // Reply-to = organisateur
        $chasse_id = get_field('enigme_chasse_associee', $enigme_id, false);
        $organisateur_id = get_organisateur_from_chasse($chasse_id);
        $email_organisateur = get_field('email_organisateur', $organisateur_id);

        if (!is_email($email_organisateur)) {
            $email_organisateur = get_option('admin_email');
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $email_organisateur
        ];

        $from_filter = static function ($name) use ($organisateur_id) {
            $titre = get_the_title($organisateur_id);
            return $titre ?: 'Chasses au Trésor';
        };
        add_filter('wp_mail_from_name', $from_filter, 10, 1);

        wp_mail($user->user_email, $sujet, $message, $headers);
        remove_filter('wp_mail_from_name', $from_filter, 10); // si mis ailleurs

    }

/**
 * Charge le script gérant la soumission automatique des réponses.
 */
function charger_script_reponse_automatique() {
    if (is_singular('enigme')) {
        $path = '/assets/js/reponse-automatique.js';
        wp_enqueue_script(
            'reponse-automatique',
            get_stylesheet_directory_uri() . $path,
            [],
            filemtime(get_stylesheet_directory() . $path),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'charger_script_reponse_automatique');

/**
 * Charge le script gérant la soumission manuelle des réponses.
 */
function charger_script_reponse_manuelle() {
    if (is_singular('enigme')) {
        $path = '/assets/js/reponse-manuelle.js';
        wp_enqueue_script(
            'reponse-manuelle',
            get_stylesheet_directory_uri() . $path,
            [],
            filemtime(get_stylesheet_directory() . $path),
            true
        );

        wp_localize_script('reponse-manuelle', 'REPONSE_MANUELLE_I18N', [
            'success'    => esc_html__('Tentative bien reçue.', 'chassesautresor-com'),
            'processing' => esc_html__('Votre tentative est en cours de traitement.', 'chassesautresor-com'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'charger_script_reponse_manuelle');

