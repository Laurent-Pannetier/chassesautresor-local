<?php

/**
 * Template: single-chasse.php (refactorisé)
 * Affichage de la fiche chasse avec header organisateur, statuts dynamiques,
 * contenus stylisés, et panneaux d’édition correctement encapsulés.
 */
defined('ABSPATH') || exit;

// 🧠 LOGIQUE MÉTIER
$chasse_id = get_the_ID();
if (!$chasse_id) {
  wp_die(__('Chasse introuvable.', 'chassesautresor-com'));
}

verifier_ou_recalculer_statut_chasse($chasse_id);
verifier_et_synchroniser_cache_enigmes_si_autorise($chasse_id);
verifier_ou_mettre_a_jour_cache_complet($chasse_id);

$edition_active     = utilisateur_peut_modifier_post($chasse_id);
$user_id            = get_current_user_id();
$est_orga_associe   = utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);
$points_utilisateur = get_user_points($user_id);

// Récupération centralisée des infos
$infos_chasse = preparer_infos_affichage_chasse($chasse_id, $user_id);

// Champs principaux
$champs = $infos_chasse['champs'];
$lot = $champs['lot'];
$titre_recompense = $champs['titre_recompense'];
$valeur_recompense = $champs['valeur_recompense'];
$cout_points = $champs['cout_points'];
$date_debut = $champs['date_debut'];
$date_fin = $champs['date_fin'];
$illimitee = $champs['illimitee'];
$nb_max = $champs['nb_max'];
$date_decouverte = $champs['date_decouverte'];
$current_stored_statut = $champs['current_stored_statut'];

$date_debut_formatee = formater_date($date_debut);
$date_fin_formatee = $illimitee ? 'Illimitée' : ($date_fin ? formater_date($date_fin) : 'Non spécifiée');
$date_decouverte_formatee = $date_decouverte ? formater_date($date_decouverte) : '';

$timestamp_debut = convertir_en_timestamp($date_debut);
$timestamp_fin = (!$illimitee && $date_fin) ? convertir_en_timestamp($date_fin) : false;
$timestamp_decouverte = convertir_en_timestamp($date_decouverte);

// Organisateur
$organisateur_id = get_organisateur_from_chasse($chasse_id);
$organisateur_nom = $organisateur_id ? get_the_title($organisateur_id) : get_the_author();



// Contenu
$description = $infos_chasse['description'];
$extrait = wp_trim_words($infos_chasse['texte_complet'], 30, '...');

$image_raw = $infos_chasse['image_raw'];
$image_id  = $infos_chasse['image_id'];
$image_url = $infos_chasse['image_url'];

$enigmes_associees = $infos_chasse['enigmes_associees'];
$total_enigmes     = $infos_chasse['total_enigmes'];
$enigmes_resolues  = compter_enigmes_resolues($chasse_id, $user_id);
$peut_ajouter_enigme = utilisateur_peut_ajouter_enigme($chasse_id);

$enigmes_incompletes = [];
foreach ($enigmes_associees as $eid) {
    verifier_ou_mettre_a_jour_cache_complet($eid);
    if (!get_field('enigme_cache_complet', $eid)) {
        $enigmes_incompletes[] = $eid;
    }
}
$has_incomplete_enigme = !empty($enigmes_incompletes);

$mode_fin = get_field('chasse_mode_fin', $chasse_id) ?: 'automatique';
$statut = $infos_chasse['statut'];
$title_filled = trim(get_the_title($chasse_id)) !== '';
$image_filled = !empty($image_id);
$description_filled = !empty(trim($description));
$required_fields_filled = $title_filled && $image_filled && $description_filled;

$needs_validatable_message = $statut === 'revision'
    && $mode_fin === 'automatique'
    && !chasse_has_validatable_enigme($chasse_id)
    && $required_fields_filled;

$statut_validation = $infos_chasse['statut_validation'];
$nb_joueurs = $infos_chasse['nb_joueurs'];

get_header();
error_log("🧪 test organisateur_associe : " . ($est_orga_associe ? 'OUI' : 'NON'));

$can_validate = peut_valider_chasse($chasse_id, $user_id);
?>

<div id="primary" class="content-area">
  <main id="main" class="site-main">

    <?php
    // 🧭 Header organisateur (dans le flux visible)
    if ($organisateur_id) {
      get_template_part('template-parts/organisateur/organisateur-header', null, [
        'organisateur_id' => $organisateur_id
      ]);
    }
    ?>

    <?php

    if ($est_orga_associe && ($has_incomplete_enigme || $needs_validatable_message)) {
        echo '<div class="cta-chasse">';
        if ($has_incomplete_enigme) {
            $warning = esc_html__(
                'Certaines énigmes doivent être complétées :',
                'chassesautresor-com'
            );
            echo '<p>⚠️ ' . $warning . '</p>';
            echo '<ul class="liste-enigmes-incompletes">';
            foreach ($enigmes_incompletes as $eid) {
                $titre = get_the_title($eid);
                $lien  = add_query_arg('edition', 'open', get_permalink($eid));
                echo '<li><a href="' . esc_url($lien) . '">' . esc_html($titre) . '</a></li>';
            }
            echo '</ul>';
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function () {';
            echo 'var t = document.getElementById("liste-enigmes");';
            echo 'if (t) { t.scrollIntoView({ behavior: "smooth" }); }';
            echo '});';
            echo '</script>';
        }
        if ($needs_validatable_message) {
            $msg = __(
                'Votre chasse se termine automatiquement ; ajoutez une énigme à validation manuelle ou automatique.',
                'chassesautresor-com'
            );
            echo '<p>⚠️ ' . esc_html($msg) . '</p>';
        }
        echo '</div>';
    } elseif ($can_validate) {
        echo '<div class="cta-chasse">';
        $msg = ($statut_validation === 'correction')
            ? 'Lorsque vous aurez terminé vos corrections, demandez sa validation :'
            : 'Lorsque vous avez finalisé votre chasse, demandez sa validation :';
        echo '<p>' . $msg . '</p>';
        echo render_form_validation_chasse($chasse_id);
        echo '</div>';
    }

    afficher_message_validation_chasse($chasse_id);
    ?>

    <?php
    if (current_user_can('administrator') && $statut_validation === 'en_attente') {
      get_template_part('template-parts/chasse/chasse-validation-actions', null, [
        'chasse_id' => $chasse_id,
      ]);
    }
    ?>

    <?php if (!empty($_GET['erreur']) && $_GET['erreur'] === 'points_insuffisants') : ?>
      <div class="message-erreur" role="alert" style="color:red; margin-bottom:1em;">
        ❌ Vous n’avez pas assez de points pour engager cette énigme.
        <a href="<?= esc_url(home_url('/boutique')); ?>">Accéder à la boutique</a>
      </div>
    <?php endif; ?>

    <!-- 📦 Fiche complète (images + méta + actions) -->
    <?php
    get_template_part('template-parts/chasse/chasse-affichage-complet', null, [
      'chasse_id'   => $chasse_id,
      'infos_chasse'=> $infos_chasse,
    ]);
    ?>

    <div class="separateur-avec-icone"></div>

    <!-- 📜 Description finale -->
    <?php
    get_template_part('template-parts/chasse/chasse-partial-description', null, [
      'description' => $description,
      'titre_recompense' => $titre_recompense,
      'lot' => $lot,
      'valeur_recompense' => $valeur_recompense,
      'nb_max' => $nb_max,
      'chasse_id' => $chasse_id,
      'mode' => 'complet'
    ]);
    ?>

    <!-- 🎯 Appel à l’action principal -->
    <?php
    $cta_data = $infos_chasse['cta_data'];

    if (($cta_data['type'] ?? '') !== 'engage') :
    ?>
      <div class="cta-chasse-row">
        <div class="cta-action"><?= $cta_data['cta_html']; ?></div>
        <div class="cta-message" aria-live="polite"><?= $cta_data['cta_message']; ?></div>
      </div>
    <?php endif; ?>


    <!-- 🧩 Liste des énigmes -->
    <section class="chasse-enigmes-wrapper" id="chasse-enigmes-wrapper">
      <header class="chasse-enigmes-header">
        <div class="barre-progression">
          <div class="remplissage" style="width: <?= ($total_enigmes ? round(100 * $enigmes_resolues / $total_enigmes) : 0); ?>%;"></div>
        </div>

        <?php if (!empty($date_decouverte_formatee)) : ?>
          <div class="meta-etiquette">🕵️‍♂️ Trouvée le <?= esc_html($date_decouverte_formatee); ?></div>
        <?php endif; ?>

        <?php
        $liens = $infos_chasse['liens'];
        $vide  = empty($liens);
        ?>
        <div class="champ-chasse champ-liens champ-fiche-publication <?= $vide ? 'champ-vide' : 'champ-rempli'; ?>"
          data-champ="chasse_principale_liens"
          data-cpt="chasse"
          data-post-id="<?= esc_attr($chasse_id); ?>">
          <div class="champ-donnees"
            data-valeurs='<?= json_encode($liens, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'></div>
          <div class="champ-affichage">
            <?= render_liens_publics($liens, 'chasse', ['afficher_titre' => false, 'wrap' => false]); ?>
          </div>
          <div class="champ-feedback"></div>
        </div>
      </header>

      <div class="titre-enigmes-wrapper">
        <h2>Énigmes</h2>
        <?php if ($peut_ajouter_enigme && $total_enigmes > 0 && !$has_incomplete_enigme) :
          get_template_part('template-parts/enigme/chasse-partial-ajout-enigme', null, [
            'has_enigmes' => true,
            'chasse_id'   => $chasse_id,
            'use_button'  => true,
          ]);
        endif; ?>
      </div>
      <div id="liste-enigmes" class="chasse-enigmes-liste">
        <?php
        get_template_part('template-parts/enigme/chasse-partial-boucle-enigmes', null, [
          'chasse_id'       => $chasse_id,
          'est_orga_associe'=> $est_orga_associe,
          'infos_chasse'    => $infos_chasse,
        ]);
        ?>
      </div>

      <footer class="chasse-enigmes-footer">

      </footer>
    </section>

  </main>
</div>

<?php
// 💬 Modale d’introduction (affichée une seule fois)
$modal_deja_vue = get_post_meta($chasse_id, 'chasse_modal_bienvenue_vue', true);

if (!$modal_deja_vue) :
  $post_bienvenue = get_post(9004);
  if ($post_bienvenue && $post_bienvenue->post_status === 'publish') :
    update_post_meta($chasse_id, 'chasse_modal_bienvenue_vue', '1');
    $contenu = apply_filters('the_content', $post_bienvenue->post_content);
?>
    <div class="modal-bienvenue-wrapper" role="dialog" aria-modal="true" aria-labelledby="modal-titre">
      <div class="modal-bienvenue-inner">
        <button class="modal-close-top" aria-label="Fermer la fenêtre">&times;</button>
        <?= $contenu; ?>
      </div>
    </div>

    <script>
      window.addEventListener('DOMContentLoaded', () => {
        const wrapper = document.querySelector('.modal-bienvenue-wrapper');
        if (!wrapper) return;
        wrapper.classList.add('visible');

        const fermer = () => wrapper.remove();

        wrapper.querySelectorAll('.modal-close-top').forEach(btn => {
          btn.addEventListener('click', fermer);
        });

        wrapper.addEventListener('click', e => {
          if (e.target === wrapper) fermer();
        });

        document.addEventListener('keydown', e => {
          if (e.key === 'Escape') fermer();
        });
      });
    </script>
    <style>
      .modal-bienvenue-wrapper {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        display: flex;
        justify-content: center;
        align-items: center;
        /* S'assure de passer au-dessus du panneau d'édition (z-index 10000) */
        z-index: 11001;
      }

      .modal-bienvenue-inner {
        background: #fff;
        padding: 2rem;
        border-radius: 1rem;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
      }

      .modal-close-top {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        color: #000;
      }
    </style>
  <?php endif; ?>
<?php endif; ?>


<?php get_footer(); ?>