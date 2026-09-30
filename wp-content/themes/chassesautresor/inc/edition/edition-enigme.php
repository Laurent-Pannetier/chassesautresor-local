<?php
defined('ABSPATH') || exit;

if (!class_exists(ChassesAuTresor\Core\Content\RiddleSolutionAttachmentService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleSolutionAttachmentService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleSolutionFilePolicyService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleSolutionFilePolicyService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleSolutionFilePublicationService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleSolutionFilePublicationService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleSolutionFileScheduler::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleSolutionFileScheduler.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleSolutionFileStorageService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleSolutionFileStorageService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleSolutionUploadService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleSolutionUploadService.php';
}

if (!class_exists(ChassesAuTresor\Core\Media\RiddleUploadDirectoryService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Media/RiddleUploadDirectoryService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleManagementService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleManagementService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleMutationService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleMutationService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleOrderingService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleOrderingService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleDeletionService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleDeletionService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleCompletionService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleCompletionService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleActionPolicyService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleActionPolicyService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleCreationService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleCreationService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleCreationRequestService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleCreationRequestService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleFieldPolicyService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleFieldPolicyService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddlePostFactory::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddlePostFactory.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleRelationshipService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleRelationshipService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleRelationshipCleanupService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleRelationshipCleanupService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleRelationshipLifecycleService::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleRelationshipLifecycleService.php';
}

if (!class_exists(ChassesAuTresor\Core\Content\RiddleRouteRegistrar::class, false)) {
    require_once dirname(__DIR__, 4)
        . '/plugins/chassesautresor-core/src/Content/RiddleRouteRegistrar.php';
}


// ==================================================
// 🧩 CRÉATION & ÉDITION D’UNE ÉNIGME
// ==================================================
// 🔹 enqueue_script_enigme_edit() → Charge JS sur single énigme
// 🔹 creer_enigme_pour_chasse() → Crée une énigme liée à une chasse
// 🔹 register_endpoint_creer_enigme() → Enregistre /creer-enigme
// 🔹 creer_enigme_et_rediriger_si_appel() → Crée une énigme et redirige
// 🔹 modifier_champ_enigme() (AJAX) → Mise à jour champs ACF ou natifs


/**
 * Charge les scripts JS nécessaires à l’édition frontale d’une énigme :
 * – Modules partagés (core)
 * – Header organisateur
 * – Panneau latéral d’édition de l’énigme
 *
 * Le script est chargé uniquement sur les pages single du CPT "enigme",
 * si l’utilisateur a les droits de modification sur ce post.
 *
 * @hook wp_enqueue_scripts
 * @return void
 */
function enqueue_script_enigme_edit()
{
  if (!is_singular('enigme')) return;

  $enigme_id = get_the_ID();
  if (!utilisateur_peut_modifier_post($enigme_id)) return;

  // 📦 Modules JS partagés + scripts spécifiques
  enqueue_core_edit_scripts([
    'edition-animation-options',
    'organisateur-edit',
    'enigme-edit',
    'enigme-stats',
    'table-etiquette',
    'tentatives-toggle',
    'solutions-pager',
    'solutions-create',
    'indices-pager',
    'indices-create',
  ]);

  wp_localize_script(
    'enigme-stats',
    'EnigmeStats',
    [
      'ajaxUrl'   => admin_url('admin-ajax.php'),
      'enigmeId'  => $enigme_id,
    ]
  );

  wp_localize_script(
    'enigme-edit',
    'ChasseSolutions',
    [
      'scrollTarget'  => '#enigme-section-solutions',
      'tooltipChasse' => __('Il existe déjà une solution pour cette chasse', 'chassesautresor-com'),
      'tooltipEnigme' => __('Il existe déjà une solution pour cette énigme', 'chassesautresor-com'),
      'toggleChasse'  => __('Voir toutes les solutions de la chasse', 'chassesautresor-com'),
      'toggleEnigme'  => __('Voir la solution de cette énigme', 'chassesautresor-com'),
    ]
  );

  // Localisation JS si besoin (ex : valeurs par défaut)
  wp_localize_script('champ-init', 'CHP_ENIGME_DEFAUT', [
    'titre' => strtolower(TITRE_DEFAUT_ENIGME),
    'image_slug' => 'defaut-enigme',
    'nonce' => wp_create_nonce('modifier_champ_enigme'),
    'deleteNonce' => wp_create_nonce('supprimer_enigme'),
  ]);

  wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'enqueue_script_enigme_edit');


/**
 * 🔹 creer_enigme_pour_chasse() → Crée une énigme liée à une chasse, avec champs ACF par défaut.
 *
 * @param int $chasse_id
 * @param int|null $user_id
 * @return int|WP_Error
 */
function creer_enigme_pour_chasse($chasse_id, $user_id = null)
{
    return ChassesAuTresor\Core\Content\RiddleCreationRouteHandler::create(
        (int) $chasse_id,
        $user_id === null ? null : (int) $user_id,
        static fn (int $huntId): ?int => get_organisateur_from_chasse($huntId)
    );
}

function initialiser_etat_enigme_creee(int $riddleId): void {
    enigme_mettre_a_jour_etat_systeme($riddleId);
}
add_action('chassesautresor_riddle_created', 'initialiser_etat_enigme_creee');


/**
 * 🔹 modifier_champ_enigme() → Gère l’enregistrement AJAX des champs ACF ou natifs du CPT énigme (post_title inclus).
 */
add_action('wp_ajax_modifier_champ_enigme', 'modifier_champ_enigme');


/**
 * @hook wp_ajax_modifier_champ_enigme
 */
function modifier_champ_enigme()
{
  check_ajax_referer('modifier_champ_enigme', 'nonce');

  if (!is_user_logged_in()) {
    wp_send_json_error('non_connecte');
  }

  $user_id = get_current_user_id();
  $champ = sanitize_text_field($_POST['champ'] ?? '');
  $valeur = $_POST['valeur'] ?? '';
  $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

  if (!$champ || !$post_id || get_post_type($post_id) !== 'enigme') {
    wp_send_json_error('⚠️ donnees_invalides');
  }

  if (!utilisateur_peut_modifier_post($post_id)) {
    wp_send_json_error('⚠️ acces_refuse');
  }

  if (!utilisateur_peut_editer_champs($post_id)) {
    wp_send_json_error('⚠️ acces_refuse');
  }

  $ancien_complet = (bool) get_field('enigme_cache_complet', $post_id);
  $field_policy   = new ChassesAuTresor\Core\Content\RiddleFieldPolicyService();

  if (!$field_policy->isEditableField($champ)) {
    wp_send_json_error('⚠️ champ_interdit');
  }

  // 🔹 Bloc interdit (pre_requis manuel)
  if ($champ === 'enigme_acces_condition' && $field_policy->isForbiddenAccessCondition((string) $valeur)) {
    wp_send_json_error('⚠️ Interdit : cette valeur est gérée automatiquement.');
  }

  $mutation = (new ChassesAuTresor\Core\Content\RiddleMutationService())->apply(
    $post_id,
    $champ,
    $valeur,
    static function (string $date) {
      return convertir_en_datetime($date, [
        'Y-m-d\TH:i',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
      ]);
    },
    strtotime(date('Y-m-d'))
  );

  if ($mutation['error'] !== null) {
    wp_send_json_error('⚠️ ' . $mutation['error']);
  }

  if ($mutation['refresh_state']) {
    enigme_mettre_a_jour_etat_systeme($post_id);
  }

  if ($mutation['terminal']) {
    wp_send_json_success(['champ' => $champ, 'valeur' => $valeur]);
  }

  if (function_exists('verifier_ou_mettre_a_jour_cache_complet')) {
    verifier_ou_mettre_a_jour_cache_complet($post_id);
  }
  $nouveau_complet = (bool) get_field('enigme_cache_complet', $post_id);
  $chasse_id = function_exists('recuperer_id_chasse_associee')
    ? (int) recuperer_id_chasse_associee($post_id)
    : 0;

  wp_send_json_success(
    (new ChassesAuTresor\Core\Content\RiddleManagementService())->getFieldUpdateResponse(
      $champ,
      $valeur,
      $ancien_complet,
      $nouveau_complet,
      $chasse_id
    )
  );
}
