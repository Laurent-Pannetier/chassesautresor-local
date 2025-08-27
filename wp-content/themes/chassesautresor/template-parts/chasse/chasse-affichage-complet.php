<?php
defined('ABSPATH') || exit;

$chasse_id = $args['chasse_id'] ?? null;
if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
  return;
}

$titre = get_the_title($chasse_id);
$champTitreParDefaut = 'nouvelle chasse';
$isTitreParDefaut = strtolower(trim($titre)) === strtolower($champTitreParDefaut);

// Récupération centralisée des informations
$infos_chasse = $args['infos_chasse'] ?? preparer_infos_affichage_chasse($chasse_id);


// Champs principaux (avec fallback direct en meta)
$champs = $infos_chasse['champs'];
$lot               = $champs['lot'];
$titre_recompense  = $champs['titre_recompense'];
$valeur_recompense = $champs['valeur_recompense'];
$date_debut        = $champs['date_debut'];
$date_fin          = $champs['date_fin'];
$illimitee         = $champs['illimitee'];
$nb_max            = $champs['nb_max'];
$cout_points       = (int) ($champs['cout_points'] ?? 0);

// Champs cachés
$date_decouverte      = $champs['date_decouverte'];
$current_stored_statut = $champs['current_stored_statut'];

// Données supplémentaires
$description   = $infos_chasse['description'];
$texte_complet = $infos_chasse['texte_complet'];
$extrait       = $infos_chasse['extrait'];
$est_tronque   = ($extrait !== $texte_complet);

$image_raw = $infos_chasse['image_raw'];
$image_id  = $infos_chasse['image_id'];
$image_url = $infos_chasse['image_url'];

// Enigmes
$enigmes_associees = $infos_chasse['enigmes_associees'];
$total_enigmes     = $infos_chasse['total_enigmes'];
$nb_joueurs        = $infos_chasse['nb_joueurs'];

// Dates
$date_debut_formatee = formater_date($date_debut);
$date_fin_formatee = $illimitee ? 'Illimitée' : ($date_fin ? formater_date($date_fin) : 'Non spécifiée');

// Edition
$edition_active = utilisateur_peut_modifier_post($chasse_id);

// Organisateur
$organisateur_id = get_organisateur_from_chasse($chasse_id);
$organisateur_nom = $organisateur_id ? get_the_title($organisateur_id) : get_the_author();


if (current_user_can('administrator')) {
  $chasse_id = get_the_ID();

  cat_debug("📦 [TEST] Statut stocké (admin) : " . get_field('chasse_cache_statut', $chasse_id));

  verifier_ou_recalculer_statut_chasse($chasse_id);


  mettre_a_jour_statuts_chasse($chasse_id);

  cat_debug("✅ [TEST] Recalcul exécuté via mettre_a_jour_statuts_chasse($chasse_id)");
}


$classe_intro = 'chasse-section-intro';
$est_complet = chasse_est_complet($chasse_id);
if ($edition_active && !$est_complet) {
  $classe_intro .= ' champ-vide-obligatoire';
}
?>


<section class="<?= esc_attr($classe_intro); ?>">

  <div class="chasse-fiche-container row">
    <?php
    $statut = $infos_chasse['statut'];
    $statut_validation = $infos_chasse['statut_validation'];
    $statut_label = ucfirst(str_replace('_', ' ', $statut));
    $statut_for_class = $statut;

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
      $statut_for_class = 'en_cours';
    }
    ?>

    <!-- 🔧 Bouton panneau édition -->
    <?php if ($edition_active) : ?>
      <button id="toggle-mode-edition-chasse" class="bouton-edition-toggle" aria-label="Activer Orgy">
        <i class="fa-solid fa-gear"></i>
      </button>
    <?php endif; ?>

    <!-- 📷 Image principale -->
    <div class="champ-chasse champ-img col-image <?= empty($image_url) ? 'champ-vide' : 'champ-rempli'; ?>"
      data-champ="chasse_principale_image"
      data-cpt="chasse"
      data-post-id="<?= esc_attr($chasse_id); ?>">

      <div class="champ-affichage">
        <div
            class="header-chasse__image"
            data-cout-label="<?= esc_attr__('Coût de participation : %d points.', 'chassesautresor-com'); ?>"
            data-pts-label="<?= esc_attr__('pts', 'chassesautresor-com'); ?>"
        >
          <span class="badge-statut statut-<?= esc_attr($statut_for_class); ?>" data-post-id="<?= esc_attr($chasse_id); ?>">
            <?= esc_html($statut_label); ?>
          </span>
          <?php if ($cout_points > 0) : ?>
            <span
                class="badge-cout"
                data-post-id="<?= esc_attr($chasse_id); ?>"
                aria-label="<?= esc_attr(sprintf(__('Coût de participation : %d points.', 'chassesautresor-com'), $cout_points)); ?>"
            >
              <?= esc_html($cout_points . ' ' . __('pts', 'chassesautresor-com')); ?>
            </span>
          <?php endif; ?>
          <?php
          echo wp_get_attachment_image(
              $image_id,
              'chasse-fiche',
              false,
              [
                  'class'       => 'chasse-image visuel-cpt img-h-max',
                  'data-cpt'    => 'chasse',
                  'data-post-id' => $chasse_id,
                  'alt'         => __('Image de la chasse', 'chassesautresor-com'),
              ]
          );
          ?>
        </div>
      </div>

      <input type="hidden" class="champ-input" value="<?= esc_attr($image_id); ?>">
      <div class="champ-feedback"></div>
    </div>


    <!-- 📟 Informations -->
    <div class="chasse-details-wrapper col-details">

      <!-- Titre dynamique -->
      <h1 class="titre-objet header-chasse"
        data-cpt="chasse"
        data-post-id="<?= esc_attr($chasse_id); ?>">
        <?= esc_html($titre); ?>
      </h1>

      <?php if ($organisateur_id): ?>
        <p class="txt-small auteur-organisateur">
          Par <a href="<?= get_permalink($organisateur_id); ?>"><?= esc_html($organisateur_nom); ?></a>
        </p>
      <?php endif; ?>

      <div class="meta-row svg-xsmall">
        <div class="meta-regular">
          <?php echo get_svg_icon('enigme'); ?> <?= esc_html($total_enigmes); ?> énigme<?= ($total_enigmes > 1 ? 's' : ''); ?> —
          <?php echo get_svg_icon('participants'); ?><?= esc_html($nb_joueurs); ?> joueur<?= ($nb_joueurs > 1 ? 's' : ''); ?>
        </div>
        <div class="meta-etiquette">
          <?php echo get_svg_icon('calendar'); ?>
          <span class="chasse-date-plage">
            <span class="date-debut"><?= esc_html($date_debut_formatee); ?></span> →
            <span class="date-fin"><?= esc_html($date_fin_formatee); ?></span>
          </span>

        </div>
      </div>

      <div class="separateur-3">
        <div class="trait-gauche"></div>
        <div class="icone-svg"></div>
        <div class="trait-droite"></div>
      </div>

      <?php if (!empty($titre_recompense) && (float) $valeur_recompense > 0) : ?>
        <div class="chasse-lot" aria-live="polite">
          <?php echo get_svg_icon('trophee'); ?>
          <?= esc_html($titre_recompense); ?> — <?= esc_html($valeur_recompense); ?> €
        </div>
      <?php endif; ?>

      <div class="bloc-discret">
        <?php if ($extrait) : ?>
          <p class="chasse-intro-extrait liste-elegante">
            <strong>Présentation :</strong> <?= esc_html($extrait); ?>
            <?php if ($est_tronque) : ?>
              <a href="#chasse-description">Voir les détails</a>
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

<?php if ($edition_active) : ?>
  <!-- 
    Templates SVG invisibles pour utilisation dynamique en JavaScript.
    Affichés uniquement en Orgy pour éviter de surcharger la page publique.
  -->
  <div id="svg-icons" style="display: none;">
    <template id="icon-free">
      <?php echo get_svg_icon('free'); ?>
    </template>
    <template id="icon-unlock">
      <?php echo get_svg_icon('unlock'); ?>
    </template>
  </div>
<?php endif; ?>

<?php
// Inclure le panneau si édition active
if ($edition_active) {
  get_template_part('template-parts/chasse/chasse-edition-main', null, [
    'chasse_id'   => $chasse_id,
    'infos_chasse' => $infos_chasse
  ]);
}
?>