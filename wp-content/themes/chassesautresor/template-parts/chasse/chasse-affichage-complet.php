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


$image_raw = $infos_chasse['image_raw'];
$image_id  = $infos_chasse['image_id'];
$image_url = $infos_chasse['image_url'];
$liens     = $infos_chasse['liens'] ?? [];

// Enigmes
$enigmes_associees = $infos_chasse['enigmes_associees'];
$total_enigmes     = $infos_chasse['total_enigmes'];
$nb_joueurs        = $infos_chasse['nb_joueurs'];
$nb_enigmes_payantes = $infos_chasse['nb_enigmes_payantes'];
$top_avances         = $infos_chasse['top_avances'];
$mode_fin            = $champs['mode_fin'] ?? 'automatique';
$title_mode          = $mode_fin === 'automatique'
    ? __('mode de fin de chasse : automatique', 'chassesautresor-com')
    : __('mode de fin de chasse : manuelle', 'chassesautresor-com');

// Dates
$date_debut_formatee = formater_date($date_debut);
$date_fin_formatee   = $illimitee
    ? __('Illimitée', 'chassesautresor-com')
    : ($date_fin ? formater_date($date_fin) : __('Non spécifiée', 'chassesautresor-com'));

$now          = current_time('timestamp');
$message_date = '';
if ($illimitee) {
    $message_date = __('illimitée', 'chassesautresor-com');
} else {
    $debut_ts = $date_debut ? strtotime($date_debut) : null;
    $fin_ts   = $date_fin ? strtotime($date_fin) : null;
    if ($debut_ts && $now < $debut_ts) {
        $diff = (int) ceil(($debut_ts - $now) / DAY_IN_SECONDS);
        $message_date = sprintf(
            _n('%d jour à attendre', '%d jours à attendre', $diff, 'chassesautresor-com'),
            $diff
        );
    } elseif ($fin_ts && $now > $fin_ts) {
        $message_date = sprintf(
            __('terminée depuis %s', 'chassesautresor-com'),
            wp_date('Y-m-d', $fin_ts)
        );
    } elseif ($fin_ts) {
        $diff = (int) ceil(($fin_ts - $now) / DAY_IN_SECONDS);
        $message_date = sprintf(
            _n('%d jour restant', '%d jours restants', $diff, 'chassesautresor-com'),
            $diff
        );
    }
}

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

  <div class="chasse-fiche-container">
    <?php
    $statut = $infos_chasse['statut'];
    $statut_validation = $infos_chasse['statut_validation'];
    $statut_label = '';
    $statut_for_class = $statut;

    if ($statut === 'revision') {
      if ($statut_validation === 'creation') {
        $statut_label = __('création', 'chassesautresor-com');
      } elseif ($statut_validation === 'correction') {
        $statut_label = __('correction', 'chassesautresor-com');
      } elseif ($statut_validation === 'en_attente') {
        $statut_label = __('en attente', 'chassesautresor-com');
      } else {
        $statut_label = __('révision', 'chassesautresor-com');
      }
    } elseif ($statut === 'payante' || $statut === 'en_cours') {
      $statut_label = __('en cours', 'chassesautresor-com');
      $statut_for_class = 'en_cours';
    } elseif ($statut === 'a_venir') {
      $statut_label = __('à venir', 'chassesautresor-com');
    } elseif ($statut === 'termine') {
      $statut_label = __('terminée', 'chassesautresor-com');
    } else {
      $statut_label = __($statut, 'chassesautresor-com');
    }
    ?>

    <div class="chasse-visuel-wrapper">
      <!-- 📷 Image principale -->
      <div class="champ-chasse champ-img <?= empty($image_url) ? 'champ-vide' : 'champ-rempli'; ?>"
        data-champ="chasse_principale_image"
        data-cpt="chasse"
        data-post-id="<?= esc_attr($chasse_id); ?>">
        <div class="champ-affichage">
          <div
              class="header-chasse__image"
              data-cout-label="<?= esc_attr__('Coût de participation : %d points.', 'chassesautresor-com'); ?>"
              data-pts-label="<?= esc_attr__('pts', 'chassesautresor-com'); ?>"
              data-mode-auto-label="<?= esc_attr__('mode de fin de chasse : automatique', 'chassesautresor-com'); ?>"
              data-mode-manuel-label="<?= esc_attr__('mode de fin de chasse : manuelle', 'chassesautresor-com'); ?>"
              data-mode-auto-icon="<?= esc_attr('<i class="fa-solid fa-bolt"></i>'); ?>"
              data-mode-manuel-icon="<?= esc_attr(get_svg_icon('hand')); ?>"
          >
              <span class="badge-statut statut-<?= esc_attr($statut_for_class); ?>"
                data-post-id="<?= esc_attr($chasse_id); ?>">
                <?= esc_html($statut_label); ?>
              </span>
              <?php if ($cout_points > 0) : ?>
                <span
                    class="badge-cout"
                    data-post-id="<?= esc_attr($chasse_id); ?>"
                    aria-label="<?= esc_attr(
                        sprintf(
                            __('Coût de participation : %d points.', 'chassesautresor-com'),
                            $cout_points
                        )
                    ); ?>"
                >
                  <?= esc_html($cout_points . ' ' . __('pts', 'chassesautresor-com')); ?>
                </span>
              <?php endif; ?>
              <span class="mode-fin-icone" title="<?= esc_attr($title_mode); ?>" aria-label="<?= esc_attr($title_mode); ?>">
                <?php if ($mode_fin === 'automatique') : ?>
                  <i class="fa-solid fa-bolt"></i>
                <?php else : ?>
                  <?= get_svg_icon('hand'); ?>
                <?php endif; ?>
              </span>
              <?php if ($image_id) : ?>
                <a
                  href="<?= esc_url(wp_get_attachment_image_url($image_id, 'full')); ?>"
                  class="fancybox image"
                >
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
                          'sizes'       => '(max-width: 800px) 100vw, 800px',
                      ]
                  );
                  ?>
                </a>
              <?php endif; ?>
            </div>
          </div>

        <input type="hidden" class="champ-input" value="<?= esc_attr($image_id); ?>">
        <div class="champ-feedback"></div>
      </div>
      <?php
      $vide = empty($liens);
      ?>
      <div class="champ-chasse champ-liens champ-fiche-publication <?= $vide ? 'champ-vide' : 'champ-rempli'; ?>"
        data-champ="chasse_principale_liens"
        data-cpt="chasse"
        data-post-id="<?= esc_attr($chasse_id); ?>">
        <div class="champ-donnees"
          data-valeurs='<?= json_encode($liens, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'></div>
        <div class="champ-affichage">
          <div class="champ-affichage-liens">
            <?= render_liens_publics($liens, 'chasse', [
                'afficher_titre' => false,
                'wrap'           => false,
                'placeholder'    => false,
            ]); ?>
          </div>
        </div>
        <div class="champ-feedback"></div>
      </div>
    </div>


    <!-- 📟 Informations -->
    <div class="chasse-details-wrapper">

      <div class="chasse-details-actions">
        <?php if (function_exists('ADDTOANY_SHARE_SAVE_BUTTON')) : ?>
          <?= ADDTOANY_SHARE_SAVE_BUTTON([
            'html_content' => get_svg_icon('share-icon'),
            'button_additional_classes' => 'chasse-share-button',
          ]); ?>
        <?php endif; ?>
        <?php if ($edition_active) : ?>
          <button id="toggle-mode-edition-chasse" class="bouton-edition-toggle" aria-label="<?php esc_attr_e('Paramètres de chasse', 'chassesautresor-com'); ?>">
            <i class="fa-solid fa-gear"></i>
          </button>
        <?php endif; ?>
      </div>

      <!-- Titre dynamique -->
      <h1 class="titre-objet header-chasse"
        data-cpt="chasse"
        data-post-id="<?= esc_attr($chasse_id); ?>">
        <?= esc_html($titre); ?>
      </h1>

      <?php if ($organisateur_id): ?>
        <p class="txt-small auteur-organisateur">
          <?php esc_html_e('Par', 'chassesautresor-com'); ?> <a href="<?= get_permalink($organisateur_id); ?>"><?= esc_html($organisateur_nom); ?></a>
        </p>
      <?php endif; ?>

      <div class="meta-row svg-xsmall">
        <div class="meta-regular">
          <?php echo get_svg_icon('enigme'); ?>
          <?= esc_html(sprintf(_n('%d énigme', '%d énigmes', $total_enigmes, 'chassesautresor-com'), $total_enigmes)); ?> —
          <?php echo get_svg_icon('participants'); ?>
          <?= esc_html(sprintf(_n('%d joueur', '%d joueurs', $nb_joueurs, 'chassesautresor-com'), $nb_joueurs)); ?>
        </div>
        <div class="meta-etiquette">
          <?php echo get_svg_icon('calendar'); ?>
          <span class="chasse-date-plage">
            <span class="date-debut"><?= esc_html($date_debut_formatee); ?></span> –
            <span class="date-fin"><?= esc_html($date_fin_formatee); ?></span>
          </span>

        </div>
      </div>

      <div class="separateur-3">
        <div class="trait-gauche"></div>
        <div class="icone-svg"></div>
        <div class="trait-droite"></div>
      </div>
        <?php
        get_template_part(
            'template-parts/chasse/chasse-partial-description',
            null,
            [
                'description' => $infos_chasse['description'] ?? '',
            ]
        );
        ?>

        <?php
        $cta_data = $infos_chasse['cta_data'] ?? [];
        ?>
        <div class="chasse-cta-section cta-chasse">
          <div class="chasse-caracteristiques">
          <div class="caracteristique caracteristique-date">
            <span class="caracteristique-label"><?= esc_html__('Date', 'chassesautresor-com'); ?></span>
            <span class="caracteristique-valeur"><?= esc_html($message_date); ?></span>
          </div>
          <?php if ($mode_fin === 'automatique') : ?>
            <div class="caracteristique">
              <span class="caracteristique-label"><?= esc_html__('Limite', 'chassesautresor-com'); ?></span>
              <span class="caracteristique-valeur nb-gagnants-affichage" data-post-id="<?= esc_attr($chasse_id); ?>">
                <?php if ((int) $nb_max === 0) : ?>
                  <?= esc_html__('illimité', 'chassesautresor-com'); ?>
                <?php else : ?>
                  <?= esc_html(sprintf(_n('%d gagnant', '%d gagnants', $nb_max, 'chassesautresor-com'), $nb_max)); ?>
                <?php endif; ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="caracteristique">
            <span class="caracteristique-label"><?= esc_html__('Fin de chasse', 'chassesautresor-com'); ?></span>
            <span class="caracteristique-valeur">
              <?= esc_html(
                  $mode_fin === 'automatique'
                      ? __('automatique', 'chassesautresor-com')
                      : __('manuelle', 'chassesautresor-com')
              ); ?>
            </span>
          </div>

          <div class="caracteristique">
            <span class="caracteristique-label"><?= esc_html__('Accès chasse', 'chassesautresor-com'); ?></span>
            <span class="caracteristique-valeur">
              <?= $cout_points > 0
                  ? sprintf(esc_html__('%d points', 'chassesautresor-com'), $cout_points)
                  : esc_html__('libre', 'chassesautresor-com'); ?>
            </span>
          </div>

        <div class="caracteristique">
          <span class="caracteristique-label"><?= esc_html__('Accès énigme', 'chassesautresor-com'); ?></span>
          <span class="caracteristique-valeur">
            <?php if ($nb_enigmes_payantes > 0) : ?>
              <?php
              $txt_enigme_payante = sprintf(
                  _n(
                      '%d énigme nécessite des points pour soumettre une tentative',
                      '%d énigmes nécessitent des points pour soumettre une tentative',
                      $nb_enigmes_payantes,
                      'chassesautresor-com'
                  ),
                  $nb_enigmes_payantes
              );
              ?>
              <?= esc_html($txt_enigme_payante); ?>
            <?php else : ?>
              <?= esc_html__('gratuit', 'chassesautresor-com'); ?>
            <?php endif; ?>
          </span>
        </div>

        <?php if ($nb_joueurs > 0) : ?>
          <div class="caracteristique">
            <span class="caracteristique-label"><?= esc_html__('Joueurs', 'chassesautresor-com'); ?></span>
            <span class="caracteristique-valeur"><?= sprintf(
                _n('%d participant', '%d participants', $nb_joueurs, 'chassesautresor-com'),
                $nb_joueurs
            ); ?></span>
          </div>
          <?php if ($top_avances['nb'] > 0 && $top_avances['enigmes'] > 0) : ?>
            <?php
            $txt_top = sprintf(
                _n(
                    '%1$d joueur a trouvé %2$d énigme',
                    '%1$d joueurs ont trouvé %2$d énigmes',
                    $top_avances['nb'],
                    'chassesautresor-com'
                ),
                $top_avances['nb'],
                $top_avances['enigmes']
            );
            ?>
            <div class="caracteristique">
              <span class="caracteristique-label"><?= esc_html__('Les + avancés', 'chassesautresor-com'); ?></span>
              <span class="caracteristique-valeur"><?= esc_html($txt_top); ?></span>
            </div>
          <?php endif; ?>
          <?php endif; ?>
          </div>

          <?php if (($cta_data['type'] ?? '') !== 'engage') : ?>
            <div class="cta-chasse-row">
              <div class="cta-action"><?= $cta_data['cta_html']; ?></div>
              <div class="cta-message" aria-live="polite"><?= $cta_data['cta_message']; ?></div>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($titre_recompense) || (float) $valeur_recompense > 0 || !empty($lot)) : ?>
            <div class="chasse-lot-complet" style="margin-top: 30px;">
                <h3>
                    <?= '🏆 ' . esc_html__('Récompense :', 'chassesautresor-com'); ?>
                    <?php if (!empty($titre_recompense)) : ?>
                        <?= esc_html($titre_recompense); ?>
                    <?php endif; ?>
                    <?php if (!empty($titre_recompense) && (float) $valeur_recompense > 0) : ?>
                        -
                    <?php endif; ?>
                    <?php if ((float) $valeur_recompense > 0) : ?>
                        <span class="badge-recompense avec-recompense"><?= esc_html($valeur_recompense); ?> €</span>
                    <?php endif; ?>
                </h3>

                <?php if (!empty($lot)) : ?>
                    <p><strong><?= esc_html__('Description complète :', 'chassesautresor-com'); ?></strong><br><?= wp_kses_post($lot); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

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