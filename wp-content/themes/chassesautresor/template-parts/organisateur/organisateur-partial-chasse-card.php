<?php
defined('ABSPATH') || exit;
/**
 * Template Part : Carte de chasse
 * Utilisation : get_template_part('template-parts/chasse-card', null, ['chasse_id' => $chasse_id]);
 */

// 🔹 Paramètres obligatoires
if (!isset($args['chasse_id']) || empty($args['chasse_id'])) return;
$chasse_id = $args['chasse_id'];
$completion_class = $args['completion_class'] ?? '';

// 🔹 Données générales
$titre      = get_the_title($chasse_id);
$permalink  = get_permalink($chasse_id);
$description = get_field('chasse_principale_description', $chasse_id);
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

// 🔹 Champs personnalisés
$champs = chasse_get_champs($chasse_id);
$titre_recompense   = $champs['titre_recompense'];
$valeur_recompense  = $champs['valeur_recompense'];
$cout_points        = $champs['cout_points'];
$date_debut         = $champs['date_debut'];
$date_fin           = $champs['date_fin'];
$illimitee          = $champs['illimitee'];
$nb_joueurs         = compter_joueurs_engages_chasse($chasse_id);
$texte_complet      = wp_strip_all_tags($description);
$extrait            = wp_trim_words($texte_complet, 60, '...');

// 🔹 Statut
verifier_ou_recalculer_statut_chasse($chasse_id);
$statut            = get_field('chasse_cache_statut', $chasse_id) ?: 'revision';
$statut_validation = get_field('chasse_cache_statut_validation', $chasse_id);
$statut_label      = ucfirst(str_replace('_', ' ', $statut));
if ($statut === 'revision') {
    if ($statut_validation === 'creation') $statut_label = 'création';
    elseif ($statut_validation === 'correction') $statut_label = 'correction';
    elseif ($statut_validation === 'en_attente') $statut_label = 'en attente';
}
$badge_class = 'statut-' . $statut;
$classe_statut = $badge_class;
$classe_verrouillee = '';

// 🔹 Énigmes
$enigmes_associees = recuperer_enigmes_associees($chasse_id);
$total_enigmes = count($enigmes_associees);

// 🔹 CTA
$user_id    = get_current_user_id();
$is_admin   = current_user_can('administrator');
$is_associe = utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);
$cta = '';
$cta_sous_label = '';

if ($is_admin || $is_associe) {
    $cta = '<a href="' . esc_url($permalink) . '" class="bouton-cta">' . esc_html__('Voir', 'chassesautresor') . '</a>';
} elseif ($statut_validation === 'valide') {
    if (!$user_id) {
        $cta = '<a href="' . esc_url(site_url('/mon-compte')) . '" class="bouton-cta">' . esc_html__("S'identifier", 'chassesautresor') . '</a>';
        $cta_sous_label = esc_html__('identification requise', 'chassesautresor');
    } else {
        $points = (int) get_field('chasse_infos_cout_points', $chasse_id);
        if ($points > 0 && !utilisateur_a_assez_de_points($user_id, $points)) {
            $cta = '<a href="' . esc_url(home_url('/boutique/')) . '" class="bouton-cta">' . esc_html__('Acheter des points', 'chassesautresor') . '</a>';
            $cta_sous_label = esc_html__("vous n'avez pas suffisamment de points", 'chassesautresor');
        } else {
            $cta = '<a href="' . esc_url($permalink) . '" class="bouton-cta">' . esc_html__('Participer', 'chassesautresor') . '</a>';
            if ($points > 0) {
                $cta_sous_label = sprintf(esc_html__('(%d points)', 'chassesautresor'), $points);
            }
        }
    }
}

// 🔹 Liens publics
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
?>

<div class="carte carte-ligne carte-chasse <?php echo esc_attr(trim("$classe_statut $classe_verrouillee $completion_class")); ?>">
    <div class="carte-ligne__image">
        <span class="badge-statut <?php echo esc_attr($badge_class); ?>" data-post-id="<?php echo esc_attr($chasse_id); ?>">
            <?php echo esc_html($statut_label); ?>
        </span>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($titre); ?>">
    </div>

    <div class="carte-ligne__contenu">
        <h3 class="carte-ligne__titre">
            <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($titre); ?></a>
        </h3>

        <div class="meta-row svg-xsmall">
            <div class="meta-regular">
                <?php echo get_svg_icon('enigme'); ?> <?php echo esc_html($total_enigmes); ?> énigme<?php echo ($total_enigmes > 1 ? 's' : ''); ?> —
                <?php echo get_svg_icon('participants'); ?><?php echo esc_html(formater_nombre_joueurs($nb_joueurs)); ?>
            </div>
            <div class="meta-etiquette">
                <?php echo get_svg_icon('calendar'); ?>
                <span class="chasse-date-plage">
                    <span class="date-debut"><?php echo esc_html(formater_date($date_debut)); ?></span> →
                    <span class="date-fin"><?php echo esc_html($illimitee ? 'Illimitée' : ($date_fin ? formater_date($date_fin) : 'Non spécifiée')); ?></span>
                </span>
            </div>
        </div>

        <?php if ($extrait) : ?>
            <p class="chasse-intro-extrait liste-elegante"><strong>Présentation :</strong> <?php echo esc_html($extrait); ?></p>
        <?php endif; ?>

        <?php if (!empty($titre_recompense) && (float) $valeur_recompense > 0) : ?>
            <div class="chasse-lot" aria-live="polite">
                <?php echo get_svg_icon('trophee'); ?>
                <?php echo esc_html($titre_recompense); ?> — <?php echo esc_html($valeur_recompense); ?> €
            </div>
        <?php endif; ?>

        <?php if (!empty($cta)) : ?>
            <div class="cta-chasse-row">
                <div class="cta-action">
                    <?php echo $cta; ?>
                    <?php if ($cta_sous_label) : ?>
                        <div class="cta-sous-label"><?php echo esc_html($cta_sous_label); ?></div>
                    <?php endif; ?>
                </div>
                <div class="cta-message" aria-live="polite">
                    <!-- Message dynamique injecté par Codex (ex : "Coût : 10 pts", "Disponible le 12/08/2025", etc.) -->
                </div>
            </div>
        <?php endif; ?>

        <?php if ($has_lien) : ?>
            <div class="carte-ligne__footer meta-etiquette">
                <div class="liens-publics-carte">
                    <?php echo render_liens_publics($liens, 'chasse'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>