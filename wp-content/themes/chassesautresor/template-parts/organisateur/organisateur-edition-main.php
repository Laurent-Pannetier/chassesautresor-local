<?php
// Panneau organisateur (affiché en mode édition)
defined('ABSPATH') || exit;

$organisateur_id = get_organisateur_id_from_context($args ?? []);
$peut_modifier   = utilisateur_peut_voir_panneau($organisateur_id);
$peut_editer     = utilisateur_peut_editer_champs($organisateur_id);


// User
$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$profil_expanded = array_intersect($roles, [ROLE_ORGANISATEUR_CREATION, 'abonne']);
$profil_expanded = !empty($profil_expanded);
$infos_expanded = !$profil_expanded;
$cache_complet  = get_field('organisateur_cache_complet', $organisateur_id);
$edition_active = in_array(ROLE_ORGANISATEUR_CREATION, $roles) && !$cache_complet;
$user_points    = function_exists('get_user_points') ? get_user_points((int) $current_user->ID) : 0;

// Post
$titre        = get_post_field('post_title', $organisateur_id);
$logo         = get_field('profil_public_logo_organisateur', $organisateur_id);
$logo_id      = is_array($logo) ? ($logo['ID'] ?? null) : $logo;
$logo_url     = $logo_id ? wp_get_attachment_image_src($logo_id, 'thumbnail')[0] : null;
$description  = get_field('description_longue', $organisateur_id);
$reseaux      = get_field('reseaux_sociaux', $organisateur_id);
$site         = get_field('lien_site_web', $organisateur_id);
$email_contact = get_field('profil_public_email_contact', $organisateur_id);

$iban = get_field('iban', $organisateur_id);
$bic  = get_field('bic', $organisateur_id);
$coordonnees_vides = empty($iban) && empty($bic);

$conversion_access   = verifier_acces_conversion(get_current_user_id());
$conversion_disabled = $conversion_access !== true;

$liens_publics = get_field('liens_publics', $organisateur_id); // ← manquant !
$liens_publics = is_array($liens_publics) ? array_filter($liens_publics, function ($entree) {
    $type_raw = $entree['type_de_lien'] ?? null;
    $url      = $entree['url_lien'] ?? null;
    $type = is_array($type_raw) ? ($type_raw[0] ?? '') : $type_raw;

    return is_string($type) && trim($type) !== '' && is_string($url) && trim($url) !== '';
}) : [];


if (function_exists('charger_script_conversion')) {
    charger_script_conversion(true);
}


$peut_editer_titre = champ_est_editable('post_title', $organisateur_id);

$is_complete = (
  !empty($titre) &&
  !empty($logo) &&
  !empty($description)
);

?>

<?php if ($peut_modifier) : ?>
  <section class="panneau-organisateur edition-panel edition-panel-organisateur edition-panel-modal<?php echo $edition_active ? ' edition-active' : ''; ?>" aria-hidden="<?php echo $edition_active ? 'false' : 'true'; ?>">

    <div class="edition-panel-header">
      <div class="edition-panel-header-top">
        <h2><i class="fa-solid fa-sliders"></i> <?= esc_html__('Panneau d\'édition organisateur', 'chassesautresor-com'); ?></h2>
        <button type="button" class="panneau-fermer" aria-label="Fermer les paramètres organisateur">✖</button>
      </div>
      <div class="edition-tabs">
        <button class="edition-tab active" data-target="organisateur-tab-param">Paramètres</button>
        <button class="edition-tab" data-target="organisateur-tab-stats">Statistiques</button>
        <button class="edition-tab" data-target="organisateur-tab-revenus">Points</button>
        <button class="edition-tab" data-target="organisateur-tab-animation">Animation</button>
      </div>
    </div>

    <div id="organisateur-tab-param" class="edition-tab-content active">
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
                  <li class="champ-organisateur champ-titre ligne-titre <?= empty($titre) ? 'champ-vide' : 'champ-rempli'; ?><?= $peut_editer_titre ? '' : ' champ-desactive'; ?>"
                    data-champ="post_title"
                    data-cpt="organisateur"
                    data-post-id="<?= esc_attr($organisateur_id); ?>">

                  <div class="champ-affichage">
                    <label for="champ-titre-organisateur">Titre <span class="champ-obligatoire">*</span></label>
                    <span class="champ-valeur">
                      <?= empty($titre) ? "renseigner le titre de l’organisateur" : esc_html($titre); ?>
                    </span>
                    <?php if ($peut_editer_titre) : ?>
                      <button type="button"
                        class="champ-modifier"
                        aria-label="Modifier le nom d’organisateur">✏️</button>
                    <?php endif; ?>
                  </div>

                  <div class="champ-edition" style="display: none;">
                    <input type="text"
                      class="champ-input"
                      maxlength="50"
                      value="<?= esc_attr($titre); ?>"
                      id="champ-titre-organisateur" <?= $peut_editer_titre ? '' : 'disabled'; ?>>
                    <button type="button" class="champ-enregistrer">✓</button>
                    <button type="button" class="champ-annuler">✖</button>
                  </div>

                  <div class="champ-feedback"></div>
                </li>

                <li class="champ-organisateur champ-img champ-logo ligne-logo <?= empty($logo_id) ? 'champ-vide' : 'champ-rempli'; ?>" data-champ="profil_public_logo_organisateur" data-cpt="organisateur" data-post-id="<?= esc_attr($organisateur_id); ?>">
                  <div class="champ-affichage">
                    <label>Logo organisateur <span class="champ-obligatoire">*</span></label>
                    <?php if ($peut_editer) : ?>
                      <button type="button"
                        class="champ-modifier"
                        aria-label="Modifier le logo"
                        data-champ="profil_public_logo_organisateur"
                        data-cpt="organisateur"
                        data-post-id="<?= esc_attr($organisateur_id); ?>">
                        <img src="<?= esc_url($logo_url ?: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='); ?>" alt="Logo de l’organisateur" />
                        <span class="champ-ajout-image">ajouter une image</span>
                        <span class="icone-modif">✏️</span>
                      </button>
                    <?php else : ?>
                      <?php if ($logo_url) : ?>
                        <img src="<?= esc_url($logo_url); ?>" alt="Logo de l’organisateur" />
                      <?php else : ?>
                        <span class="champ-ajout-image">ajouter une image</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                  <input type="hidden" class="champ-input" value="<?= esc_attr($logo_id ?? '') ?>">
                  <div class="champ-feedback"></div>
                </li>
                <?php $class_description = empty($description) ? 'champ-vide' : 'champ-rempli'; ?>
                <li class="champ-organisateur champ-description ligne-description <?= $class_description; ?>" data-champ="description_longue">
                    <label><?= esc_html__('Présentation', 'chassesautresor-com'); ?> <span class="champ-obligatoire">*</span></label>
                    <div class="champ-texte">
                        <?php if (empty(trim($description))) : ?>
                            <?php if ($peut_editer) : ?>
                                <a href="#" class="champ-ajouter ouvrir-panneau-description"
                                   data-champ="description_longue"
                                   data-cpt="organisateur"
                                   data-post-id="<?= esc_attr($organisateur_id); ?>">
                                    <?= esc_html__('ajouter', 'chassesautresor-com'); ?> <span class="icone-modif">✏️</span>
                                </a>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="champ-texte-contenu">
                                <?= esc_html(wp_trim_words(wp_strip_all_tags($description), 25)); ?>
                                <?php if ($peut_editer) : ?>
                                    <button type="button"
                                        class="champ-modifier ouvrir-panneau-description"
                                        data-champ="description_longue"
                                        data-cpt="organisateur"
                                        data-post-id="<?= esc_attr($organisateur_id); ?>"
                                        aria-label="<?= esc_attr__('Modifier la présentation', 'chassesautresor-com'); ?>">✏️</button>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </li>
              </ul>
              </div>

              <!-- SECTION 2 : Réglages -->
              <div class="resume-bloc resume-reglages">
                <h3>Réglages</h3>
                <ul class="resume-infos">

                <li
                  class="champ-organisateur champ-email-contact ligne-email <?= empty($email_contact) ? 'champ-vide' : 'champ-rempli'; ?>"
                  data-champ="email_contact"
                  data-cpt="organisateur"
                  data-post-id="<?= esc_attr($organisateur_id); ?>"
                  data-no-icon
                >
                  <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                  <div class="champ-affichage">
                    <label for="champ-email-contact">
                      Email de contact
                    </label>
                    <span class="champ-valeur">
                      <?= esc_html($email_contact ?: get_the_author_meta('user_email', get_post_field('post_author', $organisateur_id))); ?>
                    </span>
                    <button
                      type="button"
                      class="icone-info"
                      aria-label="Informations sur l’adresse email de contact"
                      onclick="alert('Quand aucune adresse n est renseignée, votre email utilisateur est utilisé par défaut.');"
                    >
                      <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                    </button>
                    <?php if ($peut_editer) : ?>
                      <button
                        type="button"
                        class="champ-modifier"
                        aria-label="Modifier l’adresse email de contact"
                      >
                        ✏️
                      </button>
                    <?php endif; ?>
                  </div>

                  <div class="champ-edition" style="display: none;">
                    <input
                      type="email"
                      maxlength="255"
                      value="<?= esc_attr($email_contact); ?>"
                      class="champ-input"
                      id="champ-email-contact"
                      placeholder="exemple@domaine.com"
                    >
                    <button type="button" class="champ-enregistrer">✓</button>
                    <button type="button" class="champ-annuler">✖</button>
                  </div>

                  <div class="champ-feedback"></div>
                </li>

                </ul>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- .edition-panel-body -->
    </div> <!-- #organisateur-tab-param -->

    <div id="organisateur-tab-stats" class="edition-tab-content" style="display:none;">
      <i class="fa-solid fa-chart-column tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-chart-column"></i> Statistiques</h2>
      </div>
      <?php get_template_part(
          'template-parts/organisateur/panneaux/organisateur-edition-statistiques',
          null,
          ['organisateur_id' => $organisateur_id]
      ); ?>
    </div>

    <div id="organisateur-tab-revenus" class="edition-tab-content" style="display:none;">
      <i class="fa-solid fa-coins tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-coins"></i> Points</h2>
      </div>
        <div class="edition-panel-body">
          <div class="dashboard-grid stats-cards">
            <div class="dashboard-card" data-stat="points">
              <i class="fa-solid fa-coins" aria-hidden="true"></i>
              <h3><?php esc_html_e('Points', 'chassesautresor-com'); ?></h3>
              <p class="stat-value"><?php echo esc_html($user_points); ?></p>
            </div>
            <div class="dashboard-card<?php echo $conversion_disabled ? ' disabled' : ''; ?>" data-stat="conversion">
              <i class="fa-solid fa-right-left" aria-hidden="true"></i>
              <h3>Conversion</h3>
              <button
                type="button"
                id="open-conversion-modal"
                class="stat-value"
              >
                <?php esc_html_e('Convertir', 'chassesautresor-com'); ?>
              </button>
            </div>
            <div class="dashboard-card" data-stat="bank-details">
              <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
              <h3>
                Coordonnées bancaires
                <button
                  type="button"
                  class="mode-fin-aide stat-help"
                  data-message="<?php echo esc_attr__(
                      'Ces informations sont nécessaires uniquement pour vous verser les gains issus de la conversion de vos points en euros. Nous ne prélevons jamais d\'argent.',
                      'chassesautresor-com'
                  ); ?>"
                  aria-label="<?php esc_attr_e('Informations sur les coordonnées bancaires', 'chassesautresor-com'); ?>"
                >
                  <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
                </button>
              </h3>
              <?php if ($peut_editer) : ?>
                <?php
                  $bank_label = $coordonnees_vides
                    ? __('Ajouter', 'chassesautresor-com')
                    : __('Éditer', 'chassesautresor-com');
                  $bank_aria = $coordonnees_vides
                    ? __('Ajouter des coordonnées bancaires', 'chassesautresor-com')
                    : __('Modifier les coordonnées bancaires', 'chassesautresor-com');
                ?>
                <a
                  id="ouvrir-coordonnees"
                  class="stat-value champ-modifier"
                  href="#"
                  aria-label="<?php echo esc_attr($bank_aria); ?>"
                  data-champ="coordonnees_bancaires"
                  data-cpt="organisateur"
                  data-post-id="<?php echo esc_attr($organisateur_id); ?>"
                  data-label-add="<?php esc_attr_e('Ajouter', 'chassesautresor-com'); ?>"
                  data-label-edit="<?php esc_attr_e('Éditer', 'chassesautresor-com'); ?>"
                  data-aria-add="<?php esc_attr_e('Ajouter des coordonnées bancaires', 'chassesautresor-com'); ?>"
                  data-aria-edit="<?php esc_attr_e('Modifier les coordonnées bancaires', 'chassesautresor-com'); ?>"
                ><?php echo esc_html($bank_label); ?></a>
              <?php endif; ?>
            </div>
          </div>
        </div> <!-- .edition-panel-body -->
    </div>

    <div id="organisateur-tab-animation" class="edition-tab-content" style="display:none;">
      <i class="fa-solid fa-bullhorn tab-watermark" aria-hidden="true"></i>
      <div class="edition-panel-header">
        <h2><i class="fa-solid fa-bullhorn"></i> Animation</h2>
      </div>
      <div class="edition-panel-body">
        <div class="edition-panel-section edition-panel-section-ligne">
          <div class="section-content">
            <div class="resume-blocs-grid">
              <div class="resume-bloc resume-visibilite">
                <h3>Communiquez</h3>
                <div class="dashboard-grid stats-cards">
                  <div class="dashboard-card champ-organisateur champ-liens <?= empty($liens_publics) ? 'champ-vide' : 'champ-rempli'; ?>"
                    data-champ="liens_publics"
                    data-cpt="organisateur"
                    data-post-id="<?= esc_attr($organisateur_id); ?>">
                    <i class="fa-solid fa-share-nodes icone-defaut" aria-hidden="true"></i>
                    <div class="champ-affichage champ-affichage-liens">
                      <?= render_liens_publics($liens_publics, 'organisateur', ['placeholder' => false]); ?>
                    </div>
                    <h3>Sites et réseaux de l'organisation</h3>
                    <?php if ($peut_modifier) : ?>
                      <a href="#"
                        class="stat-value champ-modifier ouvrir-panneau-liens"
                        data-champ="liens_publics"
                        data-cpt="organisateur"
                        data-post-id="<?= esc_attr($organisateur_id); ?>">
                        <?= empty($liens_publics) ? 'Ajouter' : 'Éditer'; ?>
                      </a>
                    <?php endif; ?>
                    <div class="champ-donnees"
                      data-valeurs='<?= json_encode($liens_publics, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'></div>
                    <div class="champ-feedback"></div>
                  </div>
                  <?php
                  $format = isset($_GET['format']) ? sanitize_key($_GET['format']) : 'png';
                  $formats_autorises = ['png', 'svg', 'eps'];
                  if (!in_array($format, $formats_autorises, true)) {
                      $format = 'png';
                  }
                  $url = get_permalink($organisateur_id);
                  $url_qr_code = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data='
                      . rawurlencode($url)
                      . '&format=' . $format;
                  ?>
                  <div class="dashboard-card champ-qr-code">
                    <img class="qr-code-icon" src="<?= esc_url($url_qr_code); ?>" alt="QR code de l'organisation">
                    <h3>QR code de votre organisation</h3>
                    <a class="stat-value" href="<?= esc_url($url_qr_code); ?>"
                      download="<?= esc_attr('qr-organisateur-' . $organisateur_id . '.' . $format); ?>">Télécharger</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="edition-panel-footer"></div>
  </section>
<?php endif; ?>
<?php if ($peut_editer) : ?>
  <?php get_template_part('template-parts/organisateur/panneaux/organisateur-edition-description', null, [
    'organisateur_id' => $organisateur_id
  ]); ?>

  <?php get_template_part('template-parts/organisateur/panneaux/organisateur-edition-liens', null, [
    'organisateur_id' => $organisateur_id
  ]); ?>

  <?php get_template_part('template-parts/organisateur/panneaux/organisateur-edition-coordonnees', null, [
    'organisateur_id' => $organisateur_id
  ]); ?>

  <?php get_template_part('template-parts/modals/modal-conversion'); ?>
<?php endif; ?>
