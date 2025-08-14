<?php

/**
 * Template Part: Panneau d'édition frontale d'une chasse
 * Requiert : $args['chasse_id']
 */

defined('ABSPATH') || exit;

$chasse_id = $args['chasse_id'] ?? null;
if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
  return;
}

$peut_modifier = utilisateur_peut_voir_panneau($chasse_id);
$peut_editer   = utilisateur_peut_editer_champs($chasse_id);
$peut_editer_titre = champ_est_editable('post_title', $chasse_id);
$peut_editer_cout  = champ_est_editable('caracteristiques.chasse_infos_cout_points', $chasse_id);

$infos_chasse = $args['infos_chasse'] ?? preparer_infos_affichage_chasse($chasse_id);

$image = $infos_chasse['image_raw'];
$description = $infos_chasse['description'];
$titre = get_the_title($chasse_id);
$liens = $infos_chasse['liens'];
$recompense = $infos_chasse['champs']['lot'];
$valeur     = $infos_chasse['champs']['valeur_recompense'];
$cout       = $infos_chasse['champs']['cout_points'];
$date_debut = $infos_chasse['champs']['date_debut'];
$date_fin   = $infos_chasse['champs']['date_fin'];
$date_decouverte = $infos_chasse['champs']['date_decouverte'];
$date_decouverte_formatee = $date_decouverte ? formater_date($date_decouverte) : '';
$gagnants = $infos_chasse['champs']['gagnants'];

// 🎯 Conversion des dates pour les champs <input>
$date_debut_obj = convertir_en_datetime($date_debut);
$date_debut_iso = $date_debut_obj ? $date_debut_obj->format('Y-m-d\TH:i') : '';

$date_fin_obj = convertir_en_datetime($date_fin);
$date_fin_iso = $date_fin_obj ? $date_fin_obj->format('Y-m-d') : '';
$illimitee  = $infos_chasse['champs']['illimitee'];
$nb_max     = $infos_chasse['champs']['nb_max'] ?: 1;
$mode_fin   = $infos_chasse['champs']['mode_fin'] ?? 'automatique';
$statut_metier = $infos_chasse['statut'] ?? 'revision';

$champTitreParDefaut = 'nouvelle chasse'; // À adapter si besoin
$isTitreParDefaut = strtolower(trim($titre)) === strtolower($champTitreParDefaut);

?>

<?php if ($peut_modifier) : ?>
  <section class="edition-panel edition-panel-chasse edition-panel-modal" data-cpt="chasse" data-post-id="<?= esc_attr($chasse_id); ?>">
    <div id="erreur-global" style="display:none; background:red; color:white; padding:5px; text-align:center; font-size:0.9em;"></div>

    <div class="edition-panel-header">
        <h2><i class="fa-solid fa-sliders"></i> <?= esc_html__('Panneau d\'édition chasse', 'chassesautresor-com'); ?></h2>
        <button type="button" class="panneau-fermer" aria-label="Fermer les paramètres">✖</button>
    </div>

    <div class="edition-tabs">
      <button class="edition-tab active" data-target="chasse-tab-param">Paramètres</button>
      <button class="edition-tab" data-target="chasse-tab-stats">Statistiques</button>
      <button class="edition-tab" data-target="chasse-tab-animation">Animation</button>
    </div>

    <div id="chasse-tab-param" class="edition-tab-content active">
      <i class="fa-solid fa-sliders tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-sliders"></i> Paramètres</h2>
      </div>
      <div class="edition-panel-body">

      <div class="edition-panel-section edition-panel-section-ligne">
        <div class="section-content">
          <div class="resume-blocs-grid">

            <!-- SECTION 1 : Informations -->
            <div class="resume-bloc resume-obligatoire">
              <h3>Informations</h3>
              <ul class="resume-infos">

                <!-- Titre -->
                <li class="champ-chasse champ-titre <?= ($isTitreParDefaut ? 'champ-vide' : 'champ-rempli'); ?><?= $peut_editer_titre ? '' : ' champ-desactive'; ?>"
                  data-champ="post_title"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>">

                  <div class="champ-affichage">
                    <label<?= $peut_editer_titre ? ' for="champ-titre-chasse"' : ''; ?>>Titre de la chasse</label>
                    <?php if ($peut_editer_titre) : ?>
                      <button type="button" class="champ-modifier" aria-label="Modifier le titre">
                        ✏️
                      </button>
                    <?php endif; ?>
                  </div>

                  <div class="champ-edition" style="display: none;">
                    <input type="text" class="champ-input" maxlength="70" value="<?= esc_attr($titre); ?>" id="champ-titre-chasse" <?= $peut_editer_titre ? '' : 'disabled'; ?>>
                    <?php if ($peut_editer_titre) : ?>
                      <button type="button" class="champ-enregistrer">✓</button>
                      <button type="button" class="champ-annuler">✖</button>
                    <?php endif; ?>
                  </div>

                  <div class="champ-feedback"></div>
                </li>
                
                <!-- Description -->
                <li class="champ-chasse champ-description <?= empty($description) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>"
                  data-champ="chasse_principale_description"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>">
                  Une description
                  <?php if ($peut_editer) : ?>
                    <button type="button"
                      class="champ-modifier ouvrir-panneau-description"
                      data-cpt="chasse"
                      data-champ="chasse_principale_description"
                      data-post-id="<?= esc_attr($chasse_id); ?>"
                      aria-label="Modifier la description">✏️</button>
                  <?php endif; ?>
                </li>

                <!-- Image -->
                <li class="champ-chasse champ-img <?= empty($image) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>"
                  data-champ="chasse_principale_image"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>">
                  Une image principale
                  <?php if ($peut_editer) : ?>
                    <button type="button"
                      class="champ-modifier"
                      data-champ="chasse_principale_image"
                      data-cpt="chasse"
                      data-post-id="<?= esc_attr($chasse_id); ?>"
                      aria-label="Modifier l’image">✏️</button>
                  <?php endif; ?>
                </li>

                <!-- Récompense -->
                <li class="champ-chasse <?= empty($recompense) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="chasse_infos_recompense_valeur" data-cpt="chasse" data-post-id="<?= esc_attr($chasse_id); ?>">
                  Récompense
                  <?php if ($peut_editer) : ?>
                    <button
                      type="button"
                      class="champ-modifier ouvrir-panneau-recompense"
                      data-champ="chasse_infos_recompense_valeur"
                      data-cpt="chasse"
                      data-post-id="<?= esc_attr($chasse_id); ?>"
                      aria-label="Modifier la récompense"
                    >✏️</button>
                  <?php endif; ?>
                </li>

              </ul>
            </div>

              <!-- SECTION 2 : Réglages -->
              <div class="resume-bloc resume-reglages">
                <h3>Réglages</h3>
                <ul class="resume-infos">

                <!-- Mode de fin de chasse -->
                  <li
                    class="champ-chasse champ-mode-fin<?= $peut_editer ? '' : ' champ-desactive'; ?>"
                    data-champ="chasse_mode_fin"
                    data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>"
                  data-no-edit="1"
                  data-no-icon="1"
                >
                  <label for="chasse_mode_fin"><?= esc_html__('Mode', 'chassesautresor-com'); ?></label>
                  <div class="champ-mode-options">
                    <label>
                      <input
                        id="chasse_mode_fin"
                        type="radio"
                        name="acf[chasse_mode_fin]"
                        value="automatique"
                        <?= $mode_fin === 'automatique' ? 'checked' : ''; ?>
                        <?= $peut_editer ? '' : 'disabled'; ?>
                      >
                      <?= esc_html__('Automatique', 'chassesautresor-com'); ?>
                      <button
                        type="button"
                        class="mode-fin-aide"
                        data-mode="automatique"
                        aria-label="<?= esc_attr__('Explication du mode automatique', 'chassesautresor-com'); ?>"
                      >
                        <i class="fa-regular fa-circle-question"></i>
                      </button>
                    </label>
                    <label>
                      <input
                        type="radio"
                        name="acf[chasse_mode_fin]"
                        value="manuelle"
                        <?= $mode_fin === 'manuelle' ? 'checked' : ''; ?>
                        <?= $peut_editer ? '' : 'disabled'; ?>
                      >
                      <?= esc_html__('Manuelle', 'chassesautresor-com'); ?>
                      <button
                        type="button"
                        class="mode-fin-aide"
                        data-mode="manuelle"
                        aria-label="<?= esc_attr__('Explication du mode manuel', 'chassesautresor-com'); ?>"
                      >
                        <i class="fa-regular fa-circle-question"></i>
                      </button>
                    </label>
                  </div>
                  <?php ob_start(); ?>
                  <?php if (in_array($statut_metier, ['payante', 'en_cours', 'revision'], true)) : ?>
                    <button
                      type="button"
                      class="terminer-chasse-btn bouton-cta"
                      data-post-id="<?= esc_attr($chasse_id); ?>"
                      data-cpt="chasse"
                      <?= ($statut_metier === 'revision') ? 'disabled' : ''; ?>
                    ><?= esc_html__('Terminer la chasse', 'chassesautresor-com'); ?></button>
                    <div class="zone-validation-fin" style="display:none;">
                      <label for="chasse-gagnants"><?= esc_html__('Gagnants', 'chassesautresor-com'); ?></label>
                      <textarea id="chasse-gagnants" required></textarea>
                      <button
                        type="button"
                        class="valider-fin-chasse-btn bouton-cta"
                        data-post-id="<?= esc_attr($chasse_id); ?>"
                        data-cpt="chasse"
                        disabled
                      ><?= esc_html__('Valider la fin de chasse', 'chassesautresor-com'); ?></button>
                      <button
                        type="button"
                        class="annuler-fin-chasse-btn bouton-secondaire"
                      ><?= esc_html__('Annuler', 'chassesautresor-com'); ?></button>
                    </div>
                  <?php endif; ?>
                  <?php $bloc_fin_chasse = trim(ob_get_clean()); ?>

                  <div class="fin-chasse-actions">
                    <?php if ($mode_fin === 'manuelle') : ?>
                      <?= $bloc_fin_chasse; ?>
                    <?php elseif ($statut_metier === 'termine') : ?>
                      <p class="message-chasse-terminee">
                        <?= sprintf(__('Chasse gagnée le %s par %s', 'chassesautresor-com'), esc_html($date_decouverte_formatee), esc_html($gagnants)); ?>
                      </p>
                    <?php endif; ?>
                  </div>
                </li>
                <?php if ($bloc_fin_chasse !== '') : ?>
                  <template id="template-fin-chasse-actions">
                    <?= $bloc_fin_chasse; ?>
                  </template>
                <?php endif; ?>

                <?php ob_start(); ?>
                <!-- Nombre de gagnants -->
                <li class="champ-chasse champ-nb-gagnants <?= empty($nb_max) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>"
                  data-champ="chasse_infos_nb_max_gagants"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>">

                  <label for="chasse-nb-gagnants">Nb gagnants</label>

                  <input type="number"
                    id="chasse-nb-gagnants"
                    name="chasse-nb-gagnants"
                    value="<?= esc_attr($nb_max); ?>"
                    min="1"
                    class="champ-inline-nb champ-nb-edit"
                    <?= ($peut_editer && $nb_max != 0) ? '' : 'disabled'; ?> />

                  <div class="champ-option-illimitee ">
                    <input type="checkbox"
                      id="nb-gagnants-illimite"
                      name="nb-gagnants-illimite"
                      <?= ($nb_max == 0 ? 'checked' : ''); ?> <?= $peut_editer ? '' : 'disabled'; ?>
                      data-champ="chasse_infos_nb_max_gagants">
                    <label for="nb-gagnants-illimite">Illimité</label>
                  </div>

                  <div id="erreur-nb-gagnants" class="message-erreur" style="display:none; color:red; font-size:0.9em; margin-top:5px;"></div>
                </li>
                <?php $bloc_nb_gagnants = ob_get_clean(); ?>

                <?php if ($mode_fin === 'automatique') : ?>
                  <?= $bloc_nb_gagnants; ?>
                <?php endif; ?>

                <template id="template-nb-gagnants">
                  <?= $bloc_nb_gagnants; ?>
                </template>

                <!-- Date de début (édition inline) -->
                <li class="champ-chasse champ-date-debut<?= $peut_editer ? '' : ' champ-desactive'; ?>"
                  data-champ="chasse_infos_date_debut"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>">

                  <label for="chasse-date-debut">Début</label>
                  <input type="datetime-local"
                    id="chasse-date-debut"
                    name="chasse-date-debut"
                    value="<?= esc_attr($date_debut_iso); ?>"
                    class="champ-inline-date champ-date-edit" <?= $peut_editer ? '' : 'disabled'; ?> required />
                  <div id="erreur-date-debut" class="message-erreur" style="display:none; color:red; font-size:0.9em; margin-top:5px;"></div>

                </li>

                <!-- Date de fin -->
                <li class="champ-chasse champ-date-fin<?= $peut_editer ? '' : ' champ-desactive'; ?>"
                  data-champ="chasse_infos_date_fin"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>">

                  <label for="chasse-date-fin">Date de fin</label>
                  <input type="date"
                    id="chasse-date-fin"
                    name="chasse-date-fin"
                    value="<?= esc_attr($date_fin_iso); ?>"
                    class="champ-inline-date champ-date-edit" <?= $peut_editer ? '' : 'disabled'; ?> />
                  <div id="erreur-date-fin" class="message-erreur" style="display:none; color:red; font-size:0.9em; margin-top:5px;"></div>

                  <div class="champ-option-illimitee">
                    <input type="checkbox"
                      id="duree-illimitee"
                      name="duree-illimitee"
                      data-champ="chasse_infos_duree_illimitee"
                      <?= ($illimitee ? 'checked' : ''); ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                    <label for="duree-illimitee">Durée illimitée</label>
                  </div>

                </li>


                <!-- Coût -->
                <li class="champ-chasse champ-cout-points <?= empty($cout) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer_cout ? '' : ' champ-desactive'; ?>"
                  data-champ="chasse_infos_cout_points"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($chasse_id); ?>">

                  <div class="champ-edition" style="display: flex; align-items: center;">
                    <label>Coût <span class="txt-small">(points)</span>
                      <button type="button" class="bouton-aide-points open-points-modal" aria-label="En savoir plus sur les points"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></button>
                    </label>

                    <input type="number"
                      class="champ-input champ-cout"
                      min="0"
                      step="1"
                      value="<?= esc_attr($cout); ?>"
                      placeholder="0" <?= $peut_editer_cout ? '' : 'disabled'; ?> />

                    <div class="champ-option-gratuit" style="margin-left: 15px;">
                      <input type="checkbox"
                        id="cout-gratuit"
                        name="cout-gratuit"
                        <?= ((int)$cout === 0) ? 'checked' : ''; ?> <?= $peut_editer_cout ? '' : 'disabled'; ?>>
                      <label for="cout-gratuit">Gratuit</label>
                    </div>
                  </div>

                  <div class="champ-feedback"></div>
                </li>

              </ul>
            </div>

          </div>
        </div>
      </div>


    </div> <!-- .edition-panel-body -->
    </div> <!-- #chasse-tab-param -->

    <div id="chasse-tab-stats" class="edition-tab-content" style="display:none;">
      <i class="fa-solid fa-chart-column tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-chart-column"></i> Statistiques</h2>
      </div>
      <?php if (!utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id)) : ?>
        <p class="edition-placeholder"><?php esc_html_e('Accès refusé.', 'chassesautresor-com'); ?></p>
      <?php else :
        if (!function_exists('chasse_compter_participants')) {
            require_once get_stylesheet_directory() . '/inc/chasse/stats.php';
        }
        $periode             = 'total';
        $nb_participants       = chasse_compter_participants($chasse_id, $periode);
        $nb_tentatives         = chasse_compter_tentatives($chasse_id, $periode);
        $nb_points             = chasse_compter_points_collectes($chasse_id, $periode);
        $total_engagements     = chasse_compter_engagements($chasse_id);
        $enigme_ids            = recuperer_ids_enigmes_pour_chasse($chasse_id);
        $enigmes_stats         = [];
        $participation_rates   = [];
        $resolution_rates      = [];
        foreach ($enigme_ids as $enigme_id) {
            $engagements = enigme_compter_joueurs_engages($enigme_id, $periode);
            $resolutions = enigme_compter_bonnes_solutions($enigme_id, 'automatique', $periode);
            $enigmes_stats[] = [
                'id'          => $enigme_id,
                'titre'       => get_the_title($enigme_id),
                'engagements' => $engagements,
                'tentatives'  => enigme_compter_tentatives($enigme_id, 'automatique', $periode),
                'points'      => enigme_compter_points_depenses($enigme_id, 'automatique', $periode),
                'resolutions' => $resolutions,
            ];
            $participation_rates[] = [
                'title' => get_the_title($enigme_id),
                'url'   => get_permalink($enigme_id),
                'value' => $total_engagements > 0 ? (100 * $engagements) / $total_engagements : 0,
            ];
            $resolution_rates[]    = [
                'title' => get_the_title($enigme_id),
                'url'   => get_permalink($enigme_id),
                'value' => $engagements > 0 ? (100 * $resolutions) / $engagements : 0,
            ];
        }
        usort($participation_rates, static function ($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        usort($resolution_rates, static function ($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        $max_participation = !empty($participation_rates) ? max(array_column($participation_rates, 'value')) : 0;
        $max_resolution   = !empty($resolution_rates) ? max(array_column($resolution_rates, 'value')) : 0;
        $par_page_participants = 25;
        $total_participants    = chasse_compter_participants($chasse_id);
        $pages_participants    = (int) ceil($total_participants / $par_page_participants);
        $total_enigmes         = count($enigme_ids);
        $participants          = chasse_lister_participants($chasse_id, $par_page_participants, 0, 'inscription', 'ASC');
      ?>
        <div class="edition-panel-body">
          <div class="stats-header" style="display:flex;align-items:center;justify-content:flex-end;gap:1rem;">
            <a href="?edition=open&amp;tab=stats" class="stats-reset"><i class="fa-solid fa-rotate-right"></i> Actualiser</a>
            <div class="stats-filtres">
              <label for="chasse-periode">Période&nbsp;:</label>
              <select id="chasse-periode">
                <option value="total">Total</option>
                <option value="jour">Aujourd’hui</option>
                <option value="semaine">Semaine</option>
                <option value="mois">Mois</option>
              </select>
            </div>
          </div>
          <div class="dashboard-grid stats-cards" id="chasse-stats">
            <?php
            get_template_part('template-parts/common/stat-card', null, [
                'icon'  => 'fa-solid fa-users',
                'label' => 'Participants',
                'value' => $nb_participants,
                'stat'  => 'participants',
            ]);
            get_template_part('template-parts/common/stat-card', null, [
                'icon'  => 'fa-solid fa-arrow-rotate-right',
                'label' => 'Tentatives',
                'value' => $nb_tentatives,
                'stat'  => 'tentatives',
            ]);
            get_template_part('template-parts/common/stat-card', null, [
                'icon'  => 'fa-solid fa-coins',
                'label' => 'Points collectés',
                'value' => $nb_points,
                'stat'  => 'points',
            ]);
            get_template_part('template-parts/common/stat-histogram-card', null, [
                'label' => 'Taux de participation',
                'data'  => $participation_rates,
                'max'   => $max_participation,
                'stat'  => 'participation',
            ]);
            get_template_part('template-parts/common/stat-histogram-card', null, [
                'label' => 'Taux de résolution',
                'data'  => $resolution_rates,
                'max'   => $max_resolution,
                'stat'  => 'resolution',
            ]);
            ?>
          </div>
          <?php get_template_part('template-parts/chasse/partials/chasse-partial-enigmes', null, [
              'enigmes' => $enigmes_stats,
              'total'   => $total_engagements,
          ]); ?>
          <div class="liste-participants" data-page="1" data-pages="<?= esc_attr($pages_participants); ?>" data-order="asc" data-orderby="inscription">
            <?php get_template_part('template-parts/chasse/partials/chasse-partial-participants', null, [
              'participants' => $participants,
              'page' => 1,
              'par_page' => $par_page_participants,
              'total' => $total_participants,
              'pages' => $pages_participants,
              'total_enigmes' => $total_enigmes,
            ]); ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div id="chasse-tab-animation" class="edition-tab-content" style="display:none;">
      <i class="fa-solid fa-bullhorn tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-bullhorn"></i> Animation</h2>
      </div>
      <div class="edition-panel-body">
        <div class="edition-panel-section edition-panel-section-ligne">
          <div class="section-content">
            <div class="resume-blocs-grid">

              <div class="resume-bloc resume-visibilite">
                <h3>Visibilité</h3>
                <ul class="resume-infos">
                  <!-- Liens -->
                  <li class="champ-chasse resume-ligne champ-liens <?= empty($liens) ? 'champ-vide' : 'champ-rempli'; ?>"
                    data-champ="chasse_principale_liens"
                    data-cpt="chasse"
                    data-post-id="<?= esc_attr($chasse_id); ?>">
                    <span class="champ-label">Sites et réseaux dédiés à cette chasse</span>
                    <?php if ($peut_modifier) : ?>
                      <button type="button"
                        class="champ-modifier ouvrir-panneau-liens"
                        data-champ="chasse_principale_liens"
                        data-cpt="chasse"
                        data-post-id="<?= esc_attr($chasse_id); ?>"
                        aria-label="Configurer les liens publics">✏️</button>
                    <?php endif; ?>
                    <div class="champ-feedback"></div>
                  </li>
                  <?php
                  if (
                      est_organisateur()
                      && ($infos_chasse['statut'] ?? '') !== 'revision'
                      && ($infos_chasse['statut_validation'] ?? '') === 'valide'
                  ) :
                      $format = isset($_GET['format']) ? sanitize_key($_GET['format']) : 'png';
                      $formats_autorises = ['png', 'svg', 'eps'];
                      if (!in_array($format, $formats_autorises, true)) {
                          $format = 'png';
                      }
                      $url = get_permalink($chasse_id);
                      $url_qr_code = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data='
                          . rawurlencode($url)
                          . '&format=' . $format;
                  ?>
                  <li class="champ-chasse resume-ligne champ-qr-code">
                    <span class="champ-label">QR code de la chasse</span>
                    <div class="qr-code-wrapper">
                      <img src="<?= esc_url($url_qr_code); ?>" alt="QR code de la chasse">
                      <a href="<?= esc_url($url_qr_code); ?>"
                        download="<?= esc_attr('qr-chasse-' . $chasse_id . '.' . $format); ?>">Télécharger</a>
                    </div>
                  </li>
                  <?php endif; ?>
                </ul>
              </div>

              <div class="resume-bloc resume-indices">
                <h3>Indices</h3>
                <ul class="resume-infos">
                  <li class="champ-chasse champ-placeholder">Section à venir</li>
                </ul>
              </div>

              <div class="resume-bloc resume-news">
                <h3>News</h3>
                <ul class="resume-infos">
                  <li class="champ-chasse champ-placeholder">Section à venir</li>
                </ul>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="edition-panel-footer">
      <?php if (current_user_can('administrator')) : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="admin-validation-actions form-traitement-validation-chasse">
          <?php wp_nonce_field('validation_admin_' . $chasse_id, 'validation_admin_nonce'); ?>
          <input type="hidden" name="action" value="traiter_validation_chasse">
          <input type="hidden" name="chasse_id" value="<?php echo esc_attr($chasse_id); ?>">
          <button type="button" class="btn-admin-danger btn-correction">
            <i class="fa-solid fa-triangle-exclamation"></i> Correction
          </button>
          <button type="submit" name="validation_admin_action" value="bannir" class="btn-admin-danger" onclick="return confirm('Bannir cette chasse&nbsp;?');">
            <i class="fa-solid fa-triangle-exclamation"></i> Bannir
          </button>
        </form>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php
// 📎 Panneaux contextuels (description, liens, etc.)
get_template_part('template-parts/chasse/panneaux/chasse-edition-description', null, [
  'chasse_id' => $chasse_id
]);
get_template_part('template-parts/chasse/panneaux/chasse-edition-recompense', null, [
  'chasse_id' => $chasse_id
]);
get_template_part('template-parts/chasse/panneaux/chasse-edition-liens', null, [
  'chasse_id' => $chasse_id
]);
?>
