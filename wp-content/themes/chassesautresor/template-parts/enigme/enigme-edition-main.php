<?php

/**
 * Template Part: Panneau d'édition frontale d'une énigme
 * Requiert : $args['enigme_id']
 */

defined('ABSPATH') || exit;

$enigme_id = $args['enigme_id'] ?? null;
if (!$enigme_id || get_post_type($enigme_id) !== 'enigme') {
  return;
}

$peut_modifier = utilisateur_peut_voir_panneau($enigme_id);
$peut_editer   = utilisateur_peut_editer_champs($enigme_id);
$peut_editer_titre = champ_est_editable('post_title', $enigme_id);

$titre = get_the_title($enigme_id);
$titre_defaut = TITRE_DEFAUT_ENIGME;
$isTitreParDefaut = strtolower(trim($titre)) === strtolower($titre_defaut);

$visuel = get_field('enigme_visuel_image', $enigme_id); // champ "gallery" → tableau d’IDs
$has_images = is_array($visuel) && count($visuel) > 0;
$legende = (string) get_field('enigme_visuel_legende', $enigme_id);
$texte_enigme = (string) get_field('enigme_visuel_texte', $enigme_id);
$reponse = get_field('enigme_reponse_bonne', $enigme_id);
$casse = get_field('enigme_reponse_casse', $enigme_id);
$max = (int) get_field('enigme_tentative_max', $enigme_id);
$cout = get_field('enigme_tentative_cout_points', $enigme_id);
$mode_validation = get_field('enigme_mode_validation', $enigme_id) ?? 'aucune';
$solution = get_field('enigme_solution', $enigme_id);
$date_raw = get_field('enigme_acces_date', $enigme_id);
$date_obj = convertir_en_datetime($date_raw);
$date_deblocage = $date_obj ? $date_obj->format('Y-m-d\TH:i') : '';


$chasse = get_field('enigme_chasse_associee', $enigme_id);
$chasse_id = is_array($chasse) ? $chasse[0] : null;
$chasse_title = $chasse_id ? get_the_title($chasse_id) : '';
$enigme_status = get_post_status($enigme_id);
$chasse_validation = $chasse_id ? get_field('chasse_cache_statut_validation', $chasse_id) : '';
$stats_locked = in_array($chasse_validation, ['creation', 'en_attente', 'correction'], true)
    || $enigme_status !== 'publish';

$nb_variantes   = 0;
$variantes_list = [];
for ($i = 1; $i <= 4; $i++) {
    $texte_variante   = trim((string) get_field("texte_{$i}", $enigme_id));
    $message_variante = trim((string) get_field("message_{$i}", $enigme_id));
    if ($texte_variante && $message_variante) {
        $nb_variantes++;
        $variantes_list[] = [
            'texte'   => $texte_variante,
            'message' => $message_variante,
        ];
    }
}
$has_variantes = ($nb_variantes > 0);


?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uid'], $_POST['action_traitement'])) {
    $uid_post = sanitize_text_field($_POST['uid']);
    check_admin_referer('traiter_tentative_' . $uid_post);
    $action = sanitize_text_field($_POST['action_traitement']);
    if (in_array($action, ['valider', 'invalider'], true)) {
        $resultat = $action === 'valider' ? 'bon' : 'faux';
        $effectue = traiter_tentative_manuelle($uid_post, $resultat);
        wp_safe_redirect(add_query_arg('done', $effectue ? '1' : '0'));
        exit;
    }
}
?>
<?php if ($peut_modifier) : ?>
  <section class="edition-panel edition-panel-enigme edition-panel-modal" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
    <div id="erreur-global"
      style="display:none; background:red; color:white; padding:5px; text-align:center; font-size:0.9em;"></div>

      <div class="edition-panel-header">
        <div class="edition-panel-header-top">
          <h2><i class="fa-solid fa-gear"></i> <?= esc_html__('Panneau d\'édition énigme', 'chassesautresor-com'); ?></h2>

        <button type="button" class="panneau-fermer" aria-label="Fermer les paramètres">✖</button>
      </div>
      <div class="edition-tabs">
        <button class="edition-tab active" data-target="enigme-tab-param">Paramètres</button>
        <button class="edition-tab" data-target="enigme-tab-stats">Statistiques</button>
        <button class="edition-tab" data-target="enigme-tab-soumission"<?= $mode_validation === 'aucune' ? ' style="display:none;"' : ''; ?>>Tentatives</button>
        <button class="edition-tab" data-target="enigme-tab-solution"><?= esc_html__('Solution', 'chassesautresor-com'); ?></button>
      </div>
    </div>

<div id="enigme-tab-param" class="edition-tab-content active">
      <i class="fa-solid fa-sliders tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-sliders"></i> Paramètres</h2>
      </div>
      <div class="edition-panel-body">
        <div class="edition-panel-section edition-panel-section-ligne">
          <div class="section-content">
            <div class="resume-blocs-grid">
              <div class="resume-bloc resume-obligatoire">

                <h3>Informations</h3>
                <ul class="resume-infos">
                  <li class="champ-enigme champ-titre <?= ($isTitreParDefaut ? 'champ-vide' : 'champ-rempli'); ?><?= $peut_editer_titre ? '' : ' champ-desactive'; ?>"
                    data-champ="post_title"
                    data-cpt="enigme"
                    data-post-id="<?= esc_attr($enigme_id); ?>"
                    data-no-edit="1">

                    <label for="champ-titre-enigme">Titre <span class="champ-obligatoire">*</span></label>
                    <input type="text"
                      class="champ-input champ-texte-edit"
                      maxlength="80"
                      value="<?= esc_attr($titre); ?>"
                      id="champ-titre-enigme" <?= $peut_editer_titre ? '' : 'disabled'; ?>
                      placeholder="renseigner le titre de l’énigme" />
                    <div class="champ-feedback"></div>
                  </li>

                  <?php
                  $has_images_utiles = enigme_a_une_image($enigme_id);
                  $images_ids       = get_field('enigme_visuel_image', $enigme_id, false);
                  ?>
                  <li class="champ-enigme champ-img <?= $has_images_utiles ? 'champ-rempli' : 'champ-vide'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>"
                    data-champ="enigme_visuel_image"
                    data-cpt="enigme"
                    data-post-id="<?= esc_attr($enigme_id); ?>"
                    data-rempli="<?= $has_images_utiles ? '1' : '0'; ?>">
                    <div class="champ-affichage">
                      <label>Illustrations <span class="champ-obligatoire">*</span></label>
                      <?php if ($peut_editer) : ?>
                        <button type="button"
                          class="champ-modifier ouvrir-panneau-images"
                          data-champ="enigme_visuel_image"
                          data-cpt="enigme"
                          data-post-id="<?= esc_attr($enigme_id); ?>">
                          <?php if ($has_images_utiles && is_array($images_ids)) : ?>
                            <?php foreach ($images_ids as $img_id) : ?>
                              <?php $thumb_url = esc_url(add_query_arg([
                                'id'     => $img_id,
                                'taille' => 'thumbnail',
                              ], site_url('/voir-image-enigme'))); ?>
                              <img src="<?= $thumb_url; ?>" alt="" class="vignette-enigme" />
                            <?php endforeach; ?>
                          <?php else : ?>
                            <span class="champ-ajout-image">ajouter</span>
                          <?php endif; ?>
                        </button>
                      <?php else : ?>
                        <?php if ($has_images_utiles && is_array($images_ids)) : ?>
                          <?php foreach ($images_ids as $img_id) : ?>
                            <?php $thumb_url = esc_url(add_query_arg([
                              'id'     => $img_id,
                              'taille' => 'thumbnail',
                            ], site_url('/voir-image-enigme'))); ?>
                            <img src="<?= $thumb_url; ?>" alt="" class="vignette-enigme" />
                          <?php endforeach; ?>
                        <?php else : ?>
                          <span class="champ-ajout-image">ajouter</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                    <div class="champ-feedback"></div>
                  </li>

                  <li class="champ-enigme champ-wysiwyg<?= empty(trim($texte_enigme)) ? ' champ-vide' : ' champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_visuel_texte" data-cpt="enigme"
                    data-post-id="<?= esc_attr($enigme_id); ?>">
                    <label><?= esc_html__('Texte énigme', 'chassesautresor-com'); ?></label>
                    <div class="champ-texte">
                        <?php if (empty(trim($texte_enigme))) : ?>
                            <?php if ($peut_editer) : ?>
                                <a href="#" class="champ-ajouter ouvrir-panneau-description"
                                   data-champ="enigme_visuel_texte"
                                   data-cpt="enigme"
                                   data-post-id="<?= esc_attr($enigme_id); ?>">
                                    <?= esc_html__('ajouter', 'chassesautresor-com'); ?>
                                </a>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="champ-texte-contenu">
                                <?= esc_html(wp_trim_words(wp_strip_all_tags($texte_enigme), 25)); ?>
                                <?php if ($peut_editer) : ?>
                                      <button type="button" class="champ-modifier ouvrir-panneau-description"
                                          data-champ="enigme_visuel_texte"
                                          data-cpt="enigme"
                                          data-post-id="<?= esc_attr($enigme_id); ?>"
                                          aria-label="<?= esc_attr__('Éditer le texte', 'chassesautresor-com'); ?>">
                                          <?= esc_html__('éditer', 'chassesautresor-com'); ?>
                                      </button>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="champ-feedback"></div>
                  </li>

                  <li class="champ-enigme champ-texte champ-soustitre<?= empty(trim($legende)) ? ' champ-vide' : ' champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>"
                    data-champ="enigme_visuel_legende" data-cpt="enigme"
                    data-post-id="<?= esc_attr($enigme_id); ?>"
                    data-no-edit="1">

                    <label for="champ-soustitre-enigme"><?= esc_html__('Sous-titre', 'chassesautresor-com'); ?></label>
                    <input type="text" class="champ-input champ-texte-edit" maxlength="100" value="<?= esc_attr($legende); ?>" id="champ-soustitre-enigme"
                      placeholder="<?= esc_attr__('Ajouter un sous-titre (max 100 caractères)', 'chassesautresor-com'); ?>" <?= $peut_editer ? '' : 'disabled'; ?> />
                    <div class="champ-feedback"></div>
                  </li>
                </ul>
              </div>

              <!-- Règlages -->
              <div class="resume-bloc resume-reglages">
                <h3>Réglages</h3>
                <div class="resume-infos">

            <!-- Mode de validation -->
            <div class="champ-enigme champ-mode-validation champ-mode-fin<?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_mode_validation" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>" data-no-edit="1" data-no-icon="1">
              <label for="enigme_mode_validation"><?= esc_html__('Validation', 'chassesautresor-com'); ?></label>
              <div class="champ-mode-options">
                <label>
                  <input id="enigme_mode_validation" type="radio" name="acf[enigme_mode_validation]" value="automatique" <?= $mode_validation === 'automatique' ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                  <?= esc_html__('Automatique', 'chassesautresor-com'); ?>
                  <?php
                  get_template_part(
                      'template-parts/common/help-icon',
                      null,
                      [
                          'aria_label' => __('Explication du mode automatique', 'chassesautresor-com'),
                          'classes'    => 'mode-fin-aide validation-aide',
                          'attributes' => [
                              'data-mode' => 'automatique',
                          ],
                      ]
                  );
                  ?>
                </label>
                <label>
                  <input type="radio" name="acf[enigme_mode_validation]" value="manuelle" <?= $mode_validation === 'manuelle' ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                  <?= esc_html__('Manuelle', 'chassesautresor-com'); ?>
                  <?php
                  get_template_part(
                      'template-parts/common/help-icon',
                      null,
                      [
                          'aria_label' => __('Explication du mode manuel', 'chassesautresor-com'),
                          'classes'    => 'mode-fin-aide validation-aide',
                          'attributes' => [
                              'data-mode' => 'manuelle',
                          ],
                      ]
                  );
                  ?>
                </label>
                <label>
                  <input type="radio" name="acf[enigme_mode_validation]" value="aucune" <?= $mode_validation === 'aucune' ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                  <?= esc_html__('Aucune', 'chassesautresor-com'); ?>
                </label>
              </div>
            </div>

            <div class="champ-enigme champ-bonne-reponse champ-groupe-reponse-automatique cache<?= empty($reponse) ? ' champ-vide' : ' champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_reponse_bonne" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
                <label for="champ-bonne-reponse">Réponse</label>
                <input type="text" id="champ-bonne-reponse" name="champ-bonne-reponse" class="champ-input champ-texte-edit" value="<?= esc_attr($reponse); ?>" placeholder="Ex : soleil" <?= $peut_editer ? '' : 'disabled'; ?> />
                <div class="champ-enigme champ-casse <?= $casse ? 'champ-rempli' : 'champ-vide'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_reponse_casse" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>" style="display: inline-flex; align-items: center;">
                  <label style="display: flex; align-items: center; gap: 4px;"><input type="checkbox" <?= $casse ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>> Respecter la casse</label>
                  <div class="champ-feedback"></div>
                </div>
                <div class="champ-feedback"></div>
              </div>

            <div class="champ-enigme champ-variantes-resume champ-groupe-reponse-automatique cache<?= $has_variantes ? ' champ-rempli' : ' champ-vide'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_reponse_variantes" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
              <label>
                <?= esc_html__('Variantes', 'chassesautresor-com'); ?>
                <?php
                get_template_part(
                    'template-parts/common/help-icon',
                    null,
                    [
                        'aria_label' => __('Explication des variantes', 'chassesautresor-com'),
                        'classes'    => 'bouton-aide-points variantes-aide',
                    ]
                );
                ?>
              </label>

              <?php if ($has_variantes) : ?>
                <table class="variantes-table">
                  <thead>
                    <tr>
                      <th scope="col"><?= esc_html__('Variante', 'chassesautresor-com'); ?></th>
                      <th scope="col"><?= esc_html__('Message', 'chassesautresor-com'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($variantes_list as $var) : ?>
                      <tr class="variante-resume">
                        <td class="variante-texte"><?= esc_html($var['texte']); ?></td>
                        <td class="variante-message"><?= esc_html($var['message']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php if ($peut_editer) : ?>
                  <button type="button" class="champ-modifier ouvrir-panneau-variantes" aria-label="<?= esc_attr__('Éditer les variantes', 'chassesautresor-com'); ?>" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
                    <?= esc_html__('éditer', 'chassesautresor-com'); ?>
                  </button>
                <?php endif; ?>
              <?php elseif ($peut_editer) : ?>
                <a href="#" class="champ-ajouter ouvrir-panneau-variantes" aria-label="<?= esc_attr__('Ajouter des variantes', 'chassesautresor-com'); ?>" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
                  <?= esc_html__('ajouter des variantes', 'chassesautresor-com'); ?>
                </a>
              <?php endif; ?>
            </div>

            <!-- Tentatives -->
            <div class="champ-enigme champ-cout-points <?= empty($cout) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_tentative.enigme_tentative_cout_points" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
              <div class="champ-edition" style="display: flex; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <label for="enigme-tentative-cout">Coût tentative
                  <?php
                  get_template_part(
                      'template-parts/common/help-icon',
                      null,
                      [
                          'aria_label' => __('En savoir plus sur les points', 'chassesautresor-com'),
                          'classes'    => 'bouton-aide-points open-points-modal',
                      ]
                  );
                  ?>
                </label>
                <input type="number" id="enigme-tentative-cout" class="champ-input champ-cout" min="0" step="1" value="<?= esc_attr($cout); ?>" placeholder="0" <?= $peut_editer ? '' : 'disabled'; ?> />
                <span class="txt-small">points</span>
                <div class="champ-option-gratuit" style="margin-left: 5px;">
                  <?php
                  $cout_normalise = trim((string)$cout);
                  $is_gratuit = $cout_normalise === '' || $cout_normalise === '0' || (int)$cout === 0;
                  ?>
                  <input type="checkbox" id="cout-gratuit-enigme" name="cout-gratuit-enigme" <?= $is_gratuit ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?> >
                  <label for="cout-gratuit-enigme">Gratuit</label>
                </div>
              </div>
              <div class="champ-feedback"></div>
            </div>

            <div class="champ-enigme champ-nb-tentatives <?= empty($max) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_tentative.enigme_tentative_max" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
              <div class="champ-edition" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                <label for="enigme-nb-tentatives">Nb tentatives
                  <?php
                  get_template_part(
                      'template-parts/common/help-icon',
                      null,
                      [
                          'aria_label' => __('Explication du nombre de tentatives', 'chassesautresor-com'),
                          'classes'    => 'bouton-aide-points tentatives-aide',
                      ]
                  );
                  ?>
                </label>
                <input type="number" id="enigme-nb-tentatives" class="champ-input champ-nb-tentatives" min="1" step="1" value="<?= esc_attr($max); ?>" placeholder="5" <?= $peut_editer ? '' : 'disabled'; ?> />
                <span class="txt-small">max par jour</span>
              </div>
              <div class="champ-feedback"></div>
            </div>

            <!-- Accès à l'énigme -->
            <?php
            $condition = get_field('enigme_acces_condition', $enigme_id) ?? 'immediat';
            $enigmes_possibles = enigme_get_liste_prerequis_possibles($enigme_id);
            $prerequis_actuels = get_field('enigme_acces_pre_requis', $enigme_id, false) ?? [];
            if (!is_array($prerequis_actuels)) {
              $prerequis_actuels = [$prerequis_actuels];
            }
            ?>
            <div class="champ-enigme champ-acces champ-mode-fin<?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_acces_condition" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>" data-no-edit="1" data-no-icon="1">
              <label for="enigme_acces_condition"><?= esc_html__('Accès', 'chassesautresor-com'); ?></label>
              <div class="champ-mode-options">
                <label>
                  <input id="enigme_acces_condition" type="radio" name="acf[enigme_acces_condition]" value="immediat" <?= $condition === 'immediat' ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                  <?= esc_html__('Libre', 'chassesautresor-com'); ?>
                </label>
                <label>
                  <input type="radio" name="acf[enigme_acces_condition]" value="date_programmee" <?= $condition === 'date_programmee' ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                  <?= esc_html__('Date programmée', 'chassesautresor-com'); ?>
                </label>
                <div id="champ-enigme-date" class="champ-enigme champ-date<?= $condition === 'date_programmee' ? '' : ' cache'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_acces_date" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
                  <input type="datetime-local" id="enigme-date-deblocage" name="enigme-date-deblocage" value="<?= esc_attr($date_deblocage); ?>" class="champ-inline-date champ-date-edit" <?= $peut_editer ? '' : 'disabled'; ?> />
                  <div class="champ-feedback champ-date-feedback" style="display:none;"></div>
                </div>
                <?php if (!empty($enigmes_possibles)) : ?>
                  <label>
                    <input type="radio" name="acf[enigme_acces_condition]" value="pre_requis" <?= $condition === 'pre_requis' ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                    <?= esc_html__('Pré-requis', 'chassesautresor-com'); ?>
                  </label>
                  <div id="champ-enigme-pre-requis" class="champ-enigme champ-pre-requis<?= $condition === 'pre_requis' ? '' : ' cache'; ?><?= $peut_editer ? '' : ' champ-desactive'; ?>" data-champ="enigme_acces_pre_requis" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>" data-vide="<?= empty($enigmes_possibles) ? '1' : '0'; ?>">
                    <?php if (empty($enigmes_possibles)) : ?>
                      <em><?= esc_html__('Aucune autre énigme disponible comme prérequis.', 'chassesautresor-com'); ?></em>
                    <?php else : ?>
                      <div class="liste-pre-requis">
                        <?php foreach ($enigmes_possibles as $id => $titre) :
                          $checked = in_array($id, $prerequis_actuels);
                          $img = get_image_enigme($id, 'thumbnail'); ?>
                          <label class="prerequis-item">
                            <input type="checkbox" value="<?= esc_attr($id); ?>" <?= $checked ? 'checked' : ''; ?> <?= $peut_editer ? '' : 'disabled'; ?>>
                            <span class="prerequis-mini">
                              <?php if ($img) : ?>
                                <img src="<?= esc_url($img); ?>" alt="" />
                              <?php endif; ?>
                              <span class="prerequis-titre"><?= esc_html($titre); ?></span>
                              <span class="prerequis-check"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                            </span>
                          </label>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    <div class="champ-feedback"></div>
                  </div>
                <?php endif; ?>
              </div>
              <div class="champ-feedback"></div>
            </div>


        </div>
        </div>
      </div>
      </div>
      </div>

      </div> <!-- .edition-panel-body -->
    <?php if (utilisateur_peut_supprimer_enigme($enigme_id)) : ?>
      <div class="edition-panel-footer">
        <button type="button" id="bouton-supprimer-enigme" class="bouton-texte secondaire">❌ Suppression énigme</button>
      </div>
    <?php endif; ?>
    </div> <!-- #enigme-tab-param -->

    <div id="enigme-tab-stats" class="edition-tab-content" style="display:none;">
      <i class="fa-solid fa-chart-column tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-chart-column"></i> Statistiques</h2>
      </div>
      <?php
      if (!function_exists('enigme_compter_joueurs_engages')) {
          require_once get_stylesheet_directory() . '/inc/enigme/stats.php';
      }
        $periode = 'total';
        if ($stats_locked) {
            $nb_participants = $nb_tentatives = $nb_points = $nb_solutions = 0;
        } else {
            $nb_participants = enigme_compter_joueurs_engages($enigme_id, $periode);
            $nb_tentatives   = enigme_compter_tentatives($enigme_id, $mode_validation, $periode);
            $nb_points       = enigme_compter_points_depenses($enigme_id, $mode_validation, $periode);
            $nb_solutions    = enigme_compter_bonnes_solutions($enigme_id, $mode_validation, $periode);
        }
      ?>
      <div class="edition-panel-body">
        <div class="stats-header" style="display:flex;align-items:center;justify-content:flex-end;gap:1rem;">
          <a href="?edition=open&amp;tab=stats" class="stats-reset"><i class="fa-solid fa-rotate-right"></i> Actualiser</a>
          <div class="stats-filtres">
            <label for="enigme-periode">Période&nbsp;:</label>
            <select id="enigme-periode">
              <option value="total">Total</option>
              <option value="jour">Aujourd’hui</option>
              <option value="semaine">Semaine</option>
              <option value="mois">Mois</option>
            </select>
          </div>
        </div>
        <div class="dashboard-grid stats-cards" id="enigme-stats">
          <?php
          $card_class = $stats_locked ? 'disabled' : '';
          get_template_part('template-parts/common/stat-card', null, [
              'icon'  => 'fa-solid fa-users',
              'label' => 'Participants',
              'value' => $nb_participants,
              'stat'  => 'participants',
              'class' => $card_class,
          ]);
          get_template_part('template-parts/common/stat-card', null, [
              'icon'  => 'fa-solid fa-arrow-rotate-right',
              'label' => 'Tentatives',
              'value' => $nb_tentatives,
              'stat'  => 'tentatives',
              'style' => $mode_validation === 'aucune' ? 'display:none;' : '',
              'class' => $card_class,
          ]);
          get_template_part('template-parts/common/stat-card', null, [
              'icon'  => 'fa-solid fa-coins',
              'label' => 'Points collectés',
              'value' => $nb_points,
              'stat'  => 'points',
              'style' => ($mode_validation === 'aucune' || (int) $cout <= 0) ? 'display:none;' : '',
              'class' => $card_class,
          ]);
          get_template_part('template-parts/common/stat-card', null, [
              'icon'  => 'fa-solid fa-check',
              'label' => 'Bonnes réponses',
              'value' => $nb_solutions,
              'stat'  => 'solutions',
              'style' => $mode_validation === 'aucune' ? 'display:none;' : '',
              'class' => $card_class,
          ]);
          ?>
        </div>
        <?php
        $resolveurs    = ($stats_locked || $mode_validation === 'aucune') ? [] : enigme_lister_resolveurs($enigme_id);
        $nb_resolveurs = count($resolveurs);
        if ($nb_resolveurs > 0) :
        ?>
        <div id="enigme-resolveurs">
          <h3>Résolue par (<?= esc_html($nb_resolveurs); ?>) joueurs</h3>
          <div class="stats-table-wrapper">
            <table class="stats-table" id="enigme-resolveurs-table">
              <thead>
                <tr>
                  <th scope="col" data-format="etiquette">Rang</th>
                  <th scope="col">Joueur</th>
                  <th scope="col">Date</th>
                  <th scope="col" data-format="etiquette">Tentatives</th>
                </tr>
              </thead>
              <tbody>
                <?php $rang = 1; foreach ($resolveurs as $res) : ?>
                <tr>
                  <td><?= esc_html($rang++); ?></td>
                  <td><?= esc_html($res['username']); ?></td>
                  <td><?= esc_html(mysql2date('d/m/Y H:i', $res['date'])); ?></td>
                  <td><?= esc_html($res['tentatives']); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <?php
        $par_page_participants = 25;
        $pages_participants    = $stats_locked ? 0 : (int) ceil($nb_participants / $par_page_participants);
        $participants         = $stats_locked ? [] : enigme_lister_participants(
            $enigme_id,
            $mode_validation,
            $par_page_participants,
            0,
            'date',
            'ASC'
        );
        ?>
        <div class="liste-participants" data-page="1" data-pages="<?= esc_attr($pages_participants); ?>" data-order="asc" data-orderby="date">
          <?php get_template_part('template-parts/enigme/partials/enigme-partial-participants', null, [
              'participants'  => $participants,
              'page'          => 1,
              'par_page'      => $par_page_participants,
              'total'         => $nb_participants,
              'pages'         => $pages_participants,
              'mode_validation' => $mode_validation,
              'orderby'       => 'date',
              'order'         => 'ASC',
              'stats_locked'  => $stats_locked,
          ]); ?>
        </div>
      </div>
    </div>

<div id="enigme-tab-soumission" class="edition-tab-content" style="display:none;">
  <i class="fa-solid fa-paper-plane tab-watermark" aria-hidden="true"></i>
  <div class="edition-panel-header">
    <h2><i class="fa-solid fa-paper-plane"></i> Tentatives <span class="total-tentatives">(<?= intval(compter_tentatives_enigme($enigme_id)); ?>)</span></h2>
  </div>
<?php
  if (!function_exists('recuperer_tentatives_enigme')) {
    require_once get_stylesheet_directory() . '/inc/enigme/tentatives.php';
  }

  $page_tentatives = max(1, intval($_GET['page_tentatives'] ?? 1));
  $par_page = 20;
  $offset = ($page_tentatives - 1) * $par_page;
  $tentatives = recuperer_tentatives_enigme($enigme_id, $par_page, $offset);
  $total_tentatives = compter_tentatives_enigme($enigme_id);
  $pages_tentatives = (int) ceil($total_tentatives / $par_page);
  ?>
  <div class="liste-tentatives" data-page="<?= esc_attr($page_tentatives); ?>" data-total="<?= esc_attr($total_tentatives); ?>" data-pages="<?= esc_attr($pages_tentatives); ?>">
    <?php get_template_part('template-parts/enigme/partials/enigme-partial-tentatives', null, [
      'tentatives' => $tentatives,
      'page'       => $page_tentatives,
      'par_page'   => $par_page,
      'total'      => $total_tentatives,
      'pages'      => $pages_tentatives,
    ]); ?>
  </div>
</div>

<div id="enigme-tab-solution" class="edition-tab-content" style="display:none;">
  <i class="fa-solid fa-key tab-watermark" aria-hidden="true"></i>
  <div class="edition-panel-header">
    <h2><i class="fa-solid fa-key"></i> <?= esc_html__('Solution de cette énigme', 'chassesautresor-com'); ?></h2>
  </div>

            <?php
            $solution_mode = get_field('enigme_solution_mode', $enigme_id) ?? 'pdf';
            $fichier      = get_field('enigme_solution_fichier', $enigme_id);
            $fichier_url  = is_array($fichier) ? $fichier['url'] : '';
            $fichier_nom  = is_array($fichier) && !empty($fichier['filename']) ? $fichier['filename'] : '';
            $explication  = get_field('enigme_solution_explication', $enigme_id);
            $explication  = is_string($explication) ? trim(wp_strip_all_tags($explication)) : '';
            $delai        = get_field('enigme_solution_delai', $enigme_id) ?? 7;
            $heure        = get_field('enigme_solution_heure', $enigme_id) ?? '18:00';
            $aide_delai   = "Les solutions ne peuvent être publiées que si une chasse est déclarée terminée. Elles restent stockées dans un coffre fort numérique jusqu'à ce que vous décidiez de les en sortir.";

            $pdf_icon_attr   = $fichier_nom ? ' style="color: var(--color-editor-success);"' : '';
            $pdf_title       = $fichier_nom ?: 'Document PDF';
            $pdf_link_text   = $fichier_nom ? 'Modifier' : 'Choisir un fichier';
            $texte_icon_attr = $explication !== '' ? ' style="color: var(--color-editor-success);"' : '';
            $texte_link_text = $explication !== '' ? 'éditer' : 'Rédiger';

            $publication_label = 'aucune solution ne';
            $publication_note  = '';

            if ($solution_mode === 'pdf') {
                if ($fichier_url !== '') {
                    $publication_label = sprintf('votre fichier %s', $fichier_nom);
                    $publication_note  = sprintf(' %d jours après la fin de la chasse, à %s', $delai, $heure);
                } else {
                    $publication_note = ' (pdf sélectionné mais pas de fichier chargé)';
                }
            } elseif ($solution_mode === 'texte') {
                if ($explication !== '') {
                    $publication_label = "votre texte d'explication";
                    $publication_note  = sprintf(', %d jours après la fin de la chasse, à %s', $delai, $heure);
                } else {
                    $publication_note = ' (rédaction libre sélectionnée mais non remplie)';
                }
            }

            $publication_message = $publication_label . ' sera affiché(e)' . $publication_note;
            ?>

            <div class="champ-enigme champ-solution">
              <div class="champ-solution-mode" data-cpt="enigme" data-post-id="<?= esc_attr($enigme_id); ?>">
                <p class="solution-publication-message"><?= esc_html($publication_message); ?></p>
                <div class="dashboard-grid solution-cards">
                    <div class="dashboard-card solution-option<?= $solution_mode === 'pdf' ? ' active' : ''; ?>" data-mode="pdf">
                      <button type="button" class="solution-reset" aria-label="Vider"<?= $fichier_nom ? '' : ' style="display:none;"'; ?>><i class="fa-solid fa-circle-xmark"></i></button>
                      <i class="fa-solid fa-file-pdf" aria-hidden="true"<?= $pdf_icon_attr; ?>></i>
                      <h3><?= esc_html($pdf_title); ?></h3>
                      <a href="#" class="stat-value"><?= esc_html($pdf_link_text); ?></a>
                      <input type="radio" name="acf[enigme_solution_mode]" value="pdf" <?= $solution_mode === 'pdf' ? 'checked' : ''; ?> hidden>
                    </div>

                    <div class="dashboard-card solution-option<?= $solution_mode === 'texte' ? ' active' : ''; ?>" data-mode="texte">
                      <button type="button" class="solution-reset" aria-label="Vider"<?= $explication !== '' ? '' : ' style="display:none;"'; ?>><i class="fa-solid fa-circle-xmark"></i></button>
                      <i class="fa-solid fa-pen-to-square" aria-hidden="true"<?= $texte_icon_attr; ?>></i>
                      <h3>Rédaction libre</h3>
                      <button type="button" id="ouvrir-panneau-solution" class="stat-value"><?= esc_html($texte_link_text); ?></button>
                      <input type="radio" name="acf[enigme_solution_mode]" value="texte" <?= $solution_mode === 'texte' ? 'checked' : ''; ?> hidden>
                    </div>

                    <div class="dashboard-card solution-delai">
                      <i class="fa-regular fa-clock" aria-hidden="true"></i>
                      <h3>
                        Délai après fin de chasse
                        <?php
                        get_template_part(
                            'template-parts/common/help-icon',
                            null,
                            [
                                'aria_label' => __('Informations sur la publication de la solution', 'chassesautresor-com'),
                                'classes'    => 'mode-fin-aide stat-help',
                                'message'    => $aide_delai,
                            ]
                        );
                        ?>
                      </h3>
                      <p class="stat-value champ-solution-timing">
                        <input
                          type="number"
                          min="0"
                          max="60"
                          step="1"
                          value="<?= esc_attr($delai); ?>"
                          id="solution-delai"
                          class="champ-input champ-delai-inline"
                        >
                        jours, publié à
                        <select id="solution-heure" class="champ-select-heure">
                          <?php foreach (range(0, 23) as $h) :
                            $formatted = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; ?>
                            <option value="<?= $formatted; ?>" <?= $formatted === $heure ? 'selected' : ''; ?>><?= $formatted; ?></option>
                          <?php endforeach; ?>
                        </select>
                        H
                      </p>
                    </div>
                  </div>
                  <div class="stats-table-wrapper">
                    <div class="dashboard-card solution-reassurance">
                      <i class="fa-solid fa-shield-halved reassurance-icon" aria-hidden="true"></i>
                      <ul>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> <strong>Vos solutions sont protégées</strong></li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Stockées dans un espace privé, hors de portée des joueurs.</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Aucun lien ne peut être trouvé ni ouvert.</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Débloquées uniquement au moment choisi.</li>
                      </ul>
                    </div>
                  </div>

                  <input type="file" id="solution-pdf-upload" accept="application/pdf" style="display:none;">
                  <div class="champ-feedback" style="margin-top: 5px;"></div>
                </div>
              </div>

          </div>
        </div>
      </div>
    </div>
    </div> <!-- #enigme-tab-solution -->
  </section>
<?php endif; ?>
