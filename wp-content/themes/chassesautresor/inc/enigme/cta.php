<?php
defined('ABSPATH') || exit;


// 🔧 CONTRÔLES ET RÉGLAGES AVANCÉS – ÉNIGMES
// 🧾 ENREGISTREMENT DES ENGAGEMENTS
// 🖼️ AFFICHAGE DES VISUELS D’ÉNIGMES
// 🎨 AFFICHAGE STYLISÉ DES ÉNIGMES
// 📬 GESTION DES RÉPONSES MANUELLES (FRONTEND)
// ✉️ ENVOI D'EMAILS (RÉPONSES MANUELLES)
// 📊 GESTION DES TENTATIVES UTILISATEUR



// ==================================================
// 🔧 CONTRÔLES ET RÉGLAGES AVANCÉS – ÉNIGMES
// ==================================================
/**
 * 🔹 enigme_get_liste_prerequis_possibles() → Retourne les autres énigmes de la même chasse pouvant être définies comme prérequis.
 * 🔹 get_cta_enigme() → Retourne les informations d'affichage du bouton CTA en fonction du statut et du contexte de l'énigme.
 * 🔹 render_cta_enigme() → Affiche le bouton CTA d'une énigme à partir des données retournées par get_cta_enigme().
 */

/**
 * 🔍 Retourne la liste des énigmes pouvant être sélectionnées comme prérequis.
 *
 * @param int $enigme_id ID de l’énigme en cours
 * @return array Tableau associatif [id => titre]
 */
function enigme_get_liste_prerequis_possibles(int $enigme_id): array
{
    $chasse = get_field('enigme_chasse_associee', $enigme_id, false);
    $chasse_id = is_object($chasse) ? $chasse->ID : (int)$chasse;
    error_log("[DEBUG] Récupération des prérequis possibles pour énigme #$enigme_id (chasse #$chasse_id)");

    if (!$chasse_id) {
        error_log("[DEBUG] Aucun chasse associée trouvée pour énigme #$enigme_id");
        return [];
    }

    $ids = recuperer_enigmes_associees($chasse_id);
    if (empty($ids)) {
        error_log("[DEBUG] Aucune énigme associée à la chasse #$chasse_id");
        return [];
    }

    $resultats = [];

    foreach ($ids as $id) {
        if ((int)$id === (int)$enigme_id) continue;

        $mode = get_field('enigme_mode_validation', $id);
        $titre = get_the_title($id);

        if (
            $mode !== 'aucune' &&
            $mode !== null &&
            stripos($titre, TITRE_DEFAUT_ENIGME) !== 0
        ) {
            $resultats[$id] = $titre;
        }
    }
    return $resultats;
}


/**
 * Normalise la valeur du mode de validation d'une énigme.
 */
function enigme_normaliser_mode_validation($mode): string
{
    if (is_array($mode)) {
        $mode = $mode['value'] ?? '';
    }

    $mode = strtolower(trim((string) $mode));

    if ($mode === '' || strpos($mode, 'aucune') === 0 || $mode === 'none') {
        return 'aucune';
    }

    return $mode;
}



/**
 * Retourne les données d’affichage du bouton d’engagement d’une énigme.
 *
 * Types possibles :
 * - voir        → lien direct réservé admin / organisateur
 * - connexion   → utilisateur non connecté
 * - engager     → première tentative ou ré-engagement possible
 * - continuer   → énigme en cours
 * - revoir      → énigme résolue
 * - terminee    → énigme finalisée (lecture seule)
 * - soumis      → réponse en attente de validation
 * - bloquee     → bloquée par la chasse, une date ou un prérequis
 * - invalide    → configuration incorrecte
 * - echouee     → tentative échouée, ré-engagement possible
 * - abandonnee  → énigme abandonnée, ré-engagement possible
 * - erreur      → statut inconnu
 *
 * @param int $enigme_id ID de l’énigme concernée
 * @param int|null $user_id ID utilisateur (optionnel, défaut : utilisateur courant)
 * @return array{
 *   etat_systeme: string,
 *   statut_utilisateur: string,
 *   type: string,
 *   label: string,
 *   sous_label: string|null,
 *   action: 'form'|'link'|'disabled',
 *   url: string|null,
 *   points: int,
 *   classe_css: string,
 *   badge: string
 * }
 */
function get_cta_enigme(int $enigme_id, ?int $user_id = null): array
{
    $user_id = $user_id ?? get_current_user_id();
    $chasse_id = recuperer_id_chasse_associee($enigme_id);

    $etat_systeme = enigme_get_etat_systeme($enigme_id);
    $statut_utilisateur = enigme_get_statut_utilisateur($enigme_id, $user_id);
    $points = intval(get_field('enigme_tentative_cout_points', $enigme_id));
    $mode_validation = enigme_normaliser_mode_validation(
        get_field('enigme_mode_validation', $enigme_id)
    );
    $chasse_terminee = $chasse_id && get_field('chasse_cache_statut', $chasse_id) === 'termine';
    if ($chasse_terminee && $etat_systeme !== 'bloquee_pre_requis') {
        $etat_systeme = 'accessible';
    }

    // Base commune
    $cta = [
        'etat_systeme'       => $etat_systeme,
        'statut_utilisateur' => $statut_utilisateur,
        'type'               => 'inconnu',
        'label'              => '',
        'sous_label'         => null,
        'action'             => 'disabled',
        'url'                => null,
        'points'             => $points,
        'classe_css'         => 'cta-inconnu',
        'badge'              => 'Indéfini',
    ];

    // 👑 Admin ou organisateur → accès direct
    if (
        current_user_can('manage_options') ||
        utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    ) {
        return array_merge($cta, [
            'type'       => 'voir',
            'label'      => 'Voir l’énigme',
            'action'     => 'link',
            'url'        => get_permalink($enigme_id),
            'classe_css' => 'cta-voir',
            'badge'      => 'Accès total',
        ]);
    }

    // 🔐 Visiteur non connecté
    if (!is_user_logged_in()) {
        return array_merge($cta, [
            'type'       => 'connexion',
            'label'      => 'Connectez-vous',
            'action'     => 'link',
            'url'        => site_url('/mon-compte'),
            'classe_css' => 'cta-connexion',
            'badge'      => 'Connexion requise',
        ]);
    }

    // 🚫 Énigme bloquée ou mal configurée
    if (!in_array($etat_systeme, ['accessible'], true)) {
        $type = in_array($etat_systeme, ['bloquee_date', 'bloquee_chasse']) ? 'bloquee' : 'invalide';
        $badge = [
            'bloquee_date'       => 'À venir',
            'bloquee_chasse'     => 'Chasse verrouillée',
            'bloquee_pre_requis' => 'Pré-requis',
            'invalide'           => 'Invalide',
            'cache_invalide'     => 'Erreur config'
        ][$etat_systeme] ?? 'Bloquée';

        return array_merge($cta, [
            'type'       => $type,
            'label'      => 'Indisponible',
            'sous_label' => 'Cette énigme est bloquée ou mal configurée.',
            'action'     => 'disabled',
            'classe_css' => 'cta-' . $type,
            'badge'      => $badge,
        ]);
    }

    // 🏁 Chasse terminée : accès libre (sauf pré-requis)
    if ($chasse_terminee && $etat_systeme !== 'bloquee_pre_requis') {
        return array_merge($cta, [
            'type'       => 'voir',
            'label'      => 'Voir',
            'action'     => 'link',
            'url'        => get_permalink($enigme_id),
            'classe_css' => 'cta-voir',
            'badge'      => 'Terminée',
            'points'     => 0,
        ]);
    }

    // ✅ Cas accessible → traitement par statut utilisateur
    switch ($statut_utilisateur) {
        case 'non_commencee':
            return array_merge($cta, [
                'type'       => 'engager',
                'label'      => "Commencer",
                'action'     => 'form',
                'url'        => site_url('/traitement-engagement'),
                'classe_css' => 'cta-engager',
                'badge'      => 'À tenter',
            ]);

        case 'en_cours':
            $type_cours   = ($mode_validation === 'aucune') ? 'voir' : 'continuer';
            $label_cours  = ($mode_validation === 'aucune')
                ? __('Voir', 'chassesautresor-com')
                : __('Continuer', 'chassesautresor-com');
            $classe_cours = ($mode_validation === 'aucune') ? 'cta-voir' : 'cta-en-cours';
            return array_merge($cta, [
                'type'       => $type_cours,
                'label'      => $label_cours,
                'action'     => 'link',
                'url'        => get_permalink($enigme_id),
                'classe_css' => $classe_cours,
                'badge'      => 'En cours',
            ]);

        case 'resolue':
            return array_merge($cta, [
                'type'       => 'revoir',
                'label'      => 'Revoir',
                'action'     => 'link',
                'url'        => get_permalink($enigme_id),
                'classe_css' => 'cta-resolue',
                'badge'      => 'Résolue',
            ]);

        case 'terminee':
            return array_merge($cta, [
                'type'       => 'terminee',
                'label'      => 'Terminée',
                'action'     => 'disabled',
                'classe_css' => 'cta-terminee',
                'badge'      => 'Clôturée',
            ]);

        case 'soumis':
            return array_merge($cta, [
                'type'       => 'soumis',
                'label'      => 'En attente',
                'action'     => 'link',
                'url'        => get_permalink($enigme_id),
                'classe_css' => 'cta-soumis',
                'badge'      => 'Soumise',
            ]);

        case 'echouee':
            return array_merge($cta, [
                'type'       => 'engager',
                'label'      => "Réessayer",
                'action'     => 'form',
                'url'        => site_url('/traitement-engagement'),
                'classe_css' => 'cta-echouee',
                'badge'      => 'Échouée',
            ]);

        case 'abandonnee':
            return array_merge($cta, [
                'type'       => 'engager',
                'label'      => "Recommencer",
                'action'     => 'form',
                'url'        => site_url('/traitement-engagement'),
                'classe_css' => 'cta-abandonnee',
                'badge'      => 'Abandonnée',
            ]);

        default:
            return array_merge($cta, [
                'type'       => 'erreur',
                'label'      => 'Erreur',
                'sous_label' => 'Statut inconnu',
                'classe_css' => 'cta-erreur',
                'badge'      => 'Erreur',
            ]);
    }
}


/**
 * @param array $cta Résultat de get_cta_enigme().
 * @param int $enigme_id ID de l’énigme concernée (utile pour les formulaires).
 */
function render_cta_enigme(array $cta, int $enigme_id): void
{
    $statut = $cta['statut_utilisateur'] ?? '';
    $classes_bouton = in_array($statut, ['non_commencee', 'echouee', 'abandonnee', 'soumis'], true)
        ? 'bouton bouton-cta'
        : 'bouton bouton-secondaire';

    switch ($cta['action']) {
        case 'form':
?>
            <form method="post" action="<?= esc_url($cta['url']); ?>" class="cta-enigme-form">
                <input type="hidden" name="enigme_id" value="<?= esc_attr($enigme_id); ?>">
                <?php wp_nonce_field('engager_enigme_' . $enigme_id, 'engager_enigme_nonce'); ?>
                <button type="submit" class="<?= esc_attr($classes_bouton); ?>">
                    <?= esc_html($cta['label']); ?>
                </button>
                <?php if (!empty($cta['sous_label'])): ?>
                    <div class="cta-sous-label"><?= esc_html($cta['sous_label']); ?></div>
                <?php endif; ?>
            </form>
        <?php
            break;

        case 'link':
        ?>
            <a href="<?= esc_url($cta['url']); ?>" class="cta-enigme-lien <?= esc_attr($classes_bouton); ?>">
                <?= esc_html($cta['label']); ?>
            </a>
            <?php if (!empty($cta['sous_label'])): ?>
                <div class="cta-sous-label"><?= esc_html($cta['sous_label']); ?></div>
            <?php endif; ?>
        <?php
            break;

        case 'disabled':
        default:
        ?>
            <p class="cta-enigme-desactive bouton-secondaire no-click">
                <?= esc_html($cta['label']); ?>
            </p>
            <?php if (!empty($cta['sous_label'])): ?>
                <div class="cta-sous-label"><?= esc_html($cta['sous_label']); ?></div>
            <?php endif; ?>
<?php
            break;
    }
}
