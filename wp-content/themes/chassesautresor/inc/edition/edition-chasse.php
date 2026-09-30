<?php
defined('ABSPATH') || exit;

if (!class_exists(ChassesAuTresor\Core\Content\HuntDateMutationService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntDateMutationService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\HuntLinkMutationService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntLinkMutationService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\HuntRewardMutationService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntRewardMutationService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\HuntFieldMutationService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntFieldMutationService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\HuntClosureService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntClosureService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\HuntCreationRequestService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntCreationRequestService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\HuntPostFactory::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntPostFactory.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\HuntDeletionService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/HuntDeletionService.php';
}

// ==================================================
// 🗺️ CRÉATION & ÉDITION D’UNE CHASSE
// ==================================================
// 🔹 enqueue_script_chasse_edit() → Charge JS sur single chasse
// 🔹 register_endpoint_creer_chasse() → Enregistre /creer-chasse
// 🔹 creer_chasse_et_rediriger_si_appel() → Crée une chasse et redirige
// 🔹 modifier_champ_chasse() → Mise à jour AJAX (champ ACF ou natif)
// 🔹 assigner_organisateur_a_chasse() → Associe l’organisateur à la chasse en `save_post`


/**
 * Charge les scripts JS frontaux pour l’édition d’une chasse (panneau édition).
 *
 * @hook wp_enqueue_scripts
 */
function enqueue_script_chasse_edit()
{
    if (!is_singular('chasse')) {
        return;
    }

    $chasse_id = get_the_ID();

    if (!utilisateur_peut_modifier_post($chasse_id)) {
        return;
    }

    // Enfile les scripts nécessaires
    enqueue_core_edit_scripts([
        'edition-animation-options',
        'chasse-edit',
        'chasse-stats',
        'table-etiquette',
        'tentatives-toggle',
        'solutions-pager',
        'solutions-create',
        'indices-pager',
        'indices-create',
    ]);
    wp_localize_script(
        'chasse-stats',
        'ChasseStats',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'chasseId' => $chasse_id,
        ]
    );
    wp_localize_script(
        'chasse-edit',
        'ChasseIndices',
        [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'chasseId'  => $chasse_id,
            'nonce'     => wp_create_nonce('hunt_management'),
            'errorText' => __('Erreur lors du chargement des indices.', 'chassesautresor-com'),
        ]
    );

    wp_localize_script(
        'chasse-edit',
        'ChasseSolutions',
        [
            'scrollTarget'  => '#chasse-section-solutions',
            'tooltipChasse' => __('Il existe déjà une solution pour cette chasse', 'chassesautresor-com'),
            'tooltipEnigme' => __('Toutes les énigmes de la chasse ont déjà une solution', 'chassesautresor-com'),
        ]
    );

    wp_localize_script(
        'chasse-edit',
        'ChasseNbGagnantsI18n',
        [
            'unlimited'    => __('illimitée', 'chassesautresor-com'),
            'winnersLabel' => __('Gagnants', 'chassesautresor-com'),
            'limitLabel'   => __('Limite', 'chassesautresor-com'),
            'single'       => _n('%d gagnant', '%d gagnants', 1, 'chassesautresor-com'),
            'plural'       => _n('%d gagnant', '%d gagnants', 2, 'chassesautresor-com'),
        ]
    );

    wp_localize_script(
        'chasse-edit',
        'ChasseModeFinI18n',
        [
            'auto'   => __('automatique', 'chassesautresor-com'),
            'manual' => __('manuelle', 'chassesautresor-com'),
        ]
    );

    // Injecte les valeurs par défaut pour JS
    wp_localize_script('champ-init', 'CHP_CHASSE_DEFAUT', [
        'titre' => strtolower(TITRE_DEFAUT_CHASSE),
        'image_slug' => 'defaut-chasse-2',
        'nonce' => wp_create_nonce('hunt_field_management'),
    ]);

    // Charge les médias pour les champs image
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'enqueue_script_chasse_edit');


/**
 * 🔹 modifier_champ_chasse() → Gère l’enregistrement AJAX des champs ACF ou natifs du CPT chasse (post_title inclus).
 */
add_action('wp_ajax_modifier_champ_chasse', 'modifier_champ_chasse');

function autoriser_modification_dates_chasse(bool $allowed, int $huntId): bool {
    return utilisateur_peut_modifier_post($huntId) && utilisateur_peut_editer_champs($huntId);
}
add_filter('chassesautresor_can_edit_hunt_dates', 'autoriser_modification_dates_chasse', 10, 2);

function actualiser_statuts_apres_modification_dates(int $huntId): void {
    mettre_a_jour_statuts_chasse($huntId);
}
add_action('chassesautresor_hunt_dates_updated', 'actualiser_statuts_apres_modification_dates');

/**
 * 🔸 Enregistrement AJAX d’un champ ACF ou natif du CPT chasse.
 *
 * Autorise :
 * - Le champ natif `post_title`
 * - Les champs ACF simples (text, number, true_false, etc.)
 * - Le répéteur `chasse_principale_liens`
 *
 * Vérifie que :
 * - L'utilisateur est connecté
 * - Il est l'auteur du post
 *
 * Les données sont sécurisées et vérifiées, même si `update_field()` retourne false.
 *
 * @hook wp_ajax_modifier_champ_chasse
 */
function modifier_champ_chasse()
{
  check_ajax_referer('hunt_field_management', 'nonce');

  if (!is_user_logged_in()) {
    wp_send_json_error('non_connecte');
  }

  $user_id = get_current_user_id();
  $champ   = sanitize_text_field($_POST['champ'] ?? '');
  $valeur  = wp_kses_post($_POST['valeur'] ?? '');
  $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

  if (!$champ || !isset($_POST['valeur'])) {
    wp_send_json_error('⚠️ donnees_invalides');
  }

  if (!$post_id || get_post_type($post_id) !== 'chasse') {
    wp_send_json_error('⚠️ post_invalide');
  }

  if (!utilisateur_peut_modifier_post($post_id)) {
    wp_send_json_error('⚠️ acces_refuse');
  }

    $demande_terminer = ($champ === 'champs_caches.chasse_cache_statut' && $valeur === 'termine');
    $champ_fin = in_array(
        $champ,
        ['champs_caches.chasse_cache_gagnants', 'champs_caches.chasse_cache_date_decouverte'],
        true
    );
    $champ_libre = ($champ === 'chasse_principale_liens');

    if (!$demande_terminer && !$champ_fin && !$champ_libre && !utilisateur_peut_editer_champs($post_id)) {
        wp_send_json_error('⚠️ acces_refuse');
    }

    $doit_recalculer_statut = false;
    $champ_valide = false;
    $reponse = ['champ' => $champ, 'valeur' => $valeur];
  // 🛡️ Initialisation sécurisée (champ simple)


  // 🔹 post_title
  if ($champ === 'post_title') {
    $ok = wp_update_post(['ID' => $post_id, 'post_title' => $valeur], true);
    if (is_wp_error($ok)) {
      wp_send_json_error('⚠️ echec_update_post_title');
    }
    wp_send_json_success($reponse);
  }

  // 🔹 chasse_principale_liens (répéteur JSON)
  if ($champ === 'chasse_principale_liens') {
    $mutation = (new ChassesAuTresor\Core\Content\HuntLinkMutationService())->apply(
      $post_id,
      (string) $valeur,
      'sanitize_text_field',
      'esc_url_raw',
      'get_field',
      'update_field'
    );
    if ($mutation['error'] !== null) {
      $message = $mutation['error'] === 'format_invalide'
        ? __('⚠️ format_invalide', 'chassesautresor-com')
        : __('⚠️ echec_mise_a_jour_liens', 'chassesautresor-com');
      wp_send_json_error($message);
    }

    wp_send_json_success(['champ' => $champ, 'valeur' => $mutation['value']]);
  }


  // 🔹 Champs récompense
  $rewardMutation = (new ChassesAuTresor\Core\Content\HuntRewardMutationService())->apply(
    $post_id,
    $champ,
    $valeur,
    'update_field'
  );
  if ($rewardMutation['error'] !== null) {
    wp_send_json_error($rewardMutation['error']);
  }
  if ($rewardMutation['handled']) {
    $champ_valide = true;
    $doit_recalculer_statut = $rewardMutation['recalculate_status'];
  }

  // 🔹 Champs standards
  $fieldMutation = (new ChassesAuTresor\Core\Content\HuntFieldMutationService())->apply(
    $post_id,
    $champ,
    $valeur,
    static fn (string $date, array $formats) => convertir_en_datetime($date, $formats),
    'sanitize_text_field',
    'update_field'
  );
  if ($fieldMutation['error'] !== null) {
    if ($fieldMutation['error'] === 'format_date_invalide') {
      $message = __('⚠️ format_date_invalide', 'chassesautresor-com');
    } elseif ($fieldMutation['error'] === 'valeur_invalide') {
      $message = __('⚠️ valeur_invalide', 'chassesautresor-com');
    } else {
      $message = __('⚠️ echec_mise_a_jour', 'chassesautresor-com');
    }
    wp_send_json_error($message);
  }
  if ($fieldMutation['handled']) {
    $champ_valide = true;
    $doit_recalculer_statut = $fieldMutation['recalculate_status'];
  }


  // 🔹 Déclenchement de la publication différée des solutions
  $completion = (new ChassesAuTresor\Core\Content\HuntClosureService())->apply(
    $post_id,
    $champ,
    $valeur,
    'update_field',
    'recuperer_enigmes_associees',
    [ChassesAuTresor\Core\Content\RiddleSolutionFileScheduler::class, 'schedule'],
    'solution_recuperer_par_objet',
    'solution_planifier_publication',
    'gerer_chasse_terminee'
  );
  if ($completion['error'] !== null) {
    wp_send_json_error(__('⚠️ echec_mise_a_jour', 'chassesautresor-com'));
  }
  if ($completion['handled']) {
    $champ_valide = true;
  }



  // 🔹 Refus des champs qui ne sont gérés par aucun service métier
  if (!$champ_valide) {
    wp_send_json_error(__('⚠️ champ_non_autorise', 'chassesautresor-com'));
  }

  // 🔁 Recalcul du statut si le champ fait partie des déclencheurs
  $champs_declencheurs_statut = [
    'caracteristiques.chasse_infos_date_debut',
    'caracteristiques.chasse_infos_date_fin',
    'caracteristiques.chasse_infos_cout_points',
    'caracteristiques.chasse_infos_duree_illimitee',
    'champs_caches.chasse_cache_statut_validation',
    'chasse_cache_statut_validation',
    'champs_caches.chasse_cache_date_decouverte',
    'chasse_cache_date_decouverte',
  ];

  if ($doit_recalculer_statut || in_array($champ, $champs_declencheurs_statut, true)) {
    wp_cache_delete($post_id, 'post');
    sleep(1); // donne une chance au cache + update ACF de se stabiliser
    $caracteristiques = get_field('chasse_infos_date_debut', $post_id);
    cat_debug("[🔁 RELOAD] Relecture avant recalcul : " . json_encode($caracteristiques));
    mettre_a_jour_statuts_chasse($post_id);
  }
  wp_send_json_success($reponse);
}
