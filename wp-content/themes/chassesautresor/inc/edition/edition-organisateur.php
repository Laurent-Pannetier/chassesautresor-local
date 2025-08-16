<?php
defined('ABSPATH') || exit;


// ==================================================
// 👤 CRÉATION & ÉDITION D’UN ORGANISATEUR
// ==================================================
// 🔹 organisateur_get_liens_actifs() → Retourne les liens publics valides d’un organisateur
// 🔹 creer_organisateur_pour_utilisateur() → Crée un CPT organisateur lié à un user
// 🔹 enqueue_script_organisateur_edit() → Charge JS si modif organisateur possible
// 🔹 modifier_champ_organisateur() (AJAX) → Enregistre champs organisateur
// 🔹 rediriger_selon_etat_organisateur() → Redirection auto selon statut
// 🔹 modifier_titre_organisateur() (AJAX) → Modifie post_title via AJAX
// 🔹 organisateur_get_liste_liens_publics() → Liste des types de lien publics
// 🔹 organisateur_get_lien_public_infos() → Détails pour un type de lien
// 🔹 pre_remplir_utilisateur_associe() → Préremplit le champ utilisateurs_associes avec l’auteur si vide

/**
 * Retourne un tableau des liens publics actifs pour un organisateur donné.
 *
 * @param int $organisateur_id ID du post organisateur.
 * @return array Tableau associatif [type => url] uniquement pour les entrées valides.
 */
function organisateur_get_liens_actifs(int $organisateur_id): array
{
  $liens_publics = get_field('liens_publics', $organisateur_id);
  $liens_actifs = [];

  if (!empty($liens_publics) && is_array($liens_publics)) {
    foreach ($liens_publics as $entree) {
      $type_raw = $entree['type_de_lien'] ?? null;
      $url      = $entree['url_lien'] ?? null;

      $type = is_array($type_raw) ? ($type_raw[0] ?? '') : $type_raw;

      if (is_string($type) && trim($type) !== '' && is_string($url) && trim($url) !== '') {
        $liens_actifs[$type] = esc_url($url);
      }
    }
  }

  return $liens_actifs;
}


/**
 * Crée un CPT "organisateur" pour un utilisateur donné, s’il n’en possède pas déjà.
 *
 * - Le post est créé avec le statut "pending"
 * - Le champ ACF "utilisateurs_associes" est rempli
 * - Le champ "profil_public" est prérempli (logo + email)
 *
 * @param int $user_id ID de l’utilisateur.
 * @return int|null ID du post créé ou null si échec ou déjà existant.
 */
function creer_organisateur_pour_utilisateur($user_id)
{
  if (!is_int($user_id) || $user_id <= 0) {
    cat_debug("❌ ID utilisateur invalide : $user_id");
    return null;
  }

  // Vérifie si un organisateur est déjà lié à cet utilisateur
  $existant = get_organisateur_from_user($user_id);
  if ($existant) {
    cat_debug("ℹ️ Un organisateur existe déjà pour l'utilisateur $user_id (ID : $existant)");
    // Renvoie simplement l'ID existant pour éviter un échec de confirmation
    return (int) $existant;
  }

  // Crée le post "organisateur" avec statut pending
  $post_id = wp_insert_post([
    'post_type'   => 'organisateur',
    'post_status' => 'pending',
    'post_title'  => TITRE_DEFAUT_ORGANISATEUR,
    'post_author' => $user_id,
  ]);

  if (is_wp_error($post_id)) {
    cat_debug("❌ Erreur création organisateur : " . $post_id->get_error_message());
    return null;
  }

  // Liaison utilisateur (champ relation)
  update_field('utilisateurs_associes', [strval($user_id)], $post_id);

  // Préremplissage logo + email
  $user_data = get_userdata($user_id);
  $email = $user_data ? $user_data->user_email : '';

  update_field('profil_public_logo_organisateur', 3927, $post_id);
  update_field('profil_public_email_contact', $email, $post_id);

  cat_debug("✅ Organisateur créé (pending) pour user $user_id : post ID $post_id");

  return $post_id;
}


/**
 * Charge les scripts JS pour l’édition frontale d’un organisateur (header + panneau).
 *
 * Chargé uniquement si l’utilisateur peut modifier l’organisateur lié.
 *
 * @hook wp_enqueue_scripts
 */
function enqueue_script_organisateur_edit()
{
  $cpts = ['organisateur', 'chasse'];

  if (!is_singular($cpts)) return;

  $post_id = get_the_ID();
  $type = get_post_type($post_id);
  $organisateur_id = null;

  if ($type === 'organisateur') {
    $organisateur_id = $post_id;
  } elseif ($type === 'chasse') {
    $organisateur_id = get_organisateur_from_chasse($post_id);

    if (!$organisateur_id && get_post_status($post_id) === 'pending') {
      $organisateur_id = get_organisateur_from_user(get_current_user_id());
    }
  }

  if ($organisateur_id && utilisateur_peut_modifier_post($organisateur_id)) {
    // 📦 Modules JS partagés + script organisateur
    enqueue_core_edit_scripts(['organisateur-edit', 'table-etiquette']);

    // ✅ Injection JavaScript APRÈS le enqueue (très important)
    $author_id = (int) get_post_field('post_author', $organisateur_id);
    $default_email = get_the_author_meta('user_email', $author_id);

    wp_localize_script('organisateur-edit', 'organisateurData', [
      'defaultEmail' => esc_js($default_email)
    ]);

    wp_enqueue_media();
  }
}
add_action('wp_enqueue_scripts', 'enqueue_script_organisateur_edit');


/**
 * 🔹 Enregistrement AJAX d’un champ ACF de l’organisateur connecté.
 */
add_action('wp_ajax_modifier_champ_organisateur', 'ajax_modifier_champ_organisateur');
function ajax_modifier_champ_organisateur()
{
  // 🛡️ Sécurité minimale : utilisateur connecté
  if (!is_user_logged_in()) {
    wp_send_json_error('non_connecte');
  }

  $user_id = get_current_user_id();
  $champ   = sanitize_text_field($_POST['champ'] ?? '');
  $valeur  = wp_kses_post($_POST['valeur'] ?? '');
  $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

  // 🧭 Si appel depuis une chasse, on remonte à l’organisateur
  if ($post_id && get_post_type($post_id) === 'chasse') {
    $post_id = get_organisateur_from_chasse($post_id);
  }

  if (!$champ || !isset($_POST['valeur'])) {
    wp_send_json_error('⚠️ donnees_invalides');
  }

  if (!$post_id) {
    wp_send_json_error('⚠️ organisateur_introuvable');
  }

  // 🔒 Vérifie que l’utilisateur est autorisé à modifier ce post
  if (!utilisateur_peut_modifier_post($post_id)) {
    wp_send_json_error('⚠️ acces_refuse');
  }

  if (!utilisateur_peut_editer_champs($post_id)) {
    wp_send_json_error('⚠️ acces_refuse');
  }

  // 🗺️ Table de correspondance si champ dans un groupe ACF
  $champ_correspondances = [
    'email_contact'                     => 'profil_public_email_contact',
    'parlez_de_vous_presentation'       => 'description_longue',
  ];

  // 🔁 Corrige le nom du champ si groupé
  $champ_cible = $champ_correspondances[$champ] ?? $champ;

  // ✏️ Titre natif WordPress
  if ($champ === 'post_title') {
    $ok = wp_update_post([
      'ID'         => $post_id,
      'post_title' => $valeur
    ], true);

    if (is_wp_error($ok)) {
      wp_send_json_error('⚠️ echec_update_post_title');
    }

    wp_send_json_success([
      'champ'  => $champ,
      'valeur' => $valeur
    ]);
  }

  // 🔗 Liens publics (répéteur)
  if ($champ === 'liens_publics') {
    $tableau = json_decode(stripslashes($valeur), true);

    if (!is_array($tableau)) {
      wp_send_json_error('⚠️ format_invalide');
    }

    $repetitions = [];
    foreach ($tableau as $ligne) {
      $type = sanitize_text_field($ligne['type_de_lien'] ?? '');
      $url  = esc_url_raw($ligne['url_lien'] ?? '');

      if ($type && $url) {
        $repetitions[] = [
          'type_de_lien' => $type,
          'url_lien'     => $url
        ];
      }
    }

    $ok = update_field('liens_publics', $repetitions, $post_id);

    // ✅ ASTUCE MAJEURE : ACF retourne false si même valeur que l’existant → comparer aussi
    $enregistre = get_field('liens_publics', $post_id);
    $enregistre = is_array($enregistre) ? array_values($enregistre) : [];
    $equivalent = json_encode($enregistre) === json_encode($repetitions);

    if ($ok || $equivalent) {
      wp_send_json_success([
        'champ'  => $champ,
        'valeur' => $repetitions
      ]);
    }

    wp_send_json_error('⚠️ echec_mise_a_jour_liens');
  }

  // 🏦 Coordonnées bancaires
  if ($champ === 'coordonnees_bancaires') {
    $donnees = json_decode(stripslashes($valeur), true);
    $iban = sanitize_text_field($donnees['iban'] ?? '');
    $bic  = sanitize_text_field($donnees['bic'] ?? '');
    $ok1 = update_field('iban', $iban, $post_id);
    $ok2 = update_field('bic', $bic, $post_id);
    // 🎯 Compatibilité avec anciens champs
    update_field('gagnez_de_largent_iban', $iban, $post_id);
    update_field('gagnez_de_largent_bic', $bic, $post_id);

    $enregistre_iban = get_field('iban', $post_id);
    $enregistre_bic  = get_field('bic', $post_id);
    $sameIban = $enregistre_iban === $iban;
    $sameBic  = $enregistre_bic === $bic;
    if (($ok1 !== false && $ok2 !== false) || ($sameIban && $sameBic)) {
      wp_send_json_success([
        'champ'  => $champ,
        'valeur' => ['iban' => $iban, 'bic' => $bic]
      ]);
    }

    wp_send_json_error('⚠️ echec_mise_a_jour_coordonnees');
  }

  // ✅ Autres champs ACF simples
  if ($champ_cible === 'description_longue') {
    $texte = trim(strip_tags((string) $valeur));
    if (mb_strlen($texte) < 50) {
      wp_send_json_error('⚠️ description_trop_courte');
    }
  }

  $ok = update_field($champ_cible, is_numeric($valeur) ? (int) $valeur : $valeur, $post_id);

  // 🔍 Vérifie via get_post_meta en fallback
  $valeur_meta = get_post_meta($post_id, $champ_cible, true);
  $valeur_comparee = stripslashes_deep($valeur);

  if ($ok || trim((string) $valeur_meta) === trim((string) $valeur_comparee)) {
    wp_send_json_success([
      'champ'  => $champ,
      'valeur' => $valeur
    ]);
  }

  wp_send_json_error('⚠️ echec_mise_a_jour_final');
}


/**
 * Redirige l’utilisateur connecté selon l’état de son CPT "organisateur".
 *
 * - Si aucun organisateur : ne fait rien
 * - Si statut "draft" ou "pending" : redirige vers la prévisualisation
 * - Si statut "publish" : redirige vers la page publique
 *
 * @return void
 */
function rediriger_selon_etat_organisateur()
{
  if (!is_user_logged_in()) {
    return;
  }

  $user_id = get_current_user_id();
  $organisateur_id = get_organisateur_from_user($user_id);

  if (!$organisateur_id) {
    return; // Aucun organisateur : accès au canevas autorisé
  }

  $user  = wp_get_current_user();
  $roles = (array) $user->roles;

  $has_chasse_non_attente = false;
  $query = get_chasses_de_organisateur($organisateur_id);
  if ($query && $query->have_posts()) {
    foreach ($query->posts as $chasse) {
      $statut_validation = get_field('chasse_cache_statut_validation', $chasse->ID);
      if ($statut_validation !== 'en_attente') {
        $has_chasse_non_attente = true;
        break;
      }
    }
  }

  if ((in_array(ROLE_ORGANISATEUR_CREATION, $roles, true) || in_array(ROLE_ORGANISATEUR, $roles, true)) && $has_chasse_non_attente) {
    return; // Laisser accès à la page, pas de redirection
  }

  $post = get_post($organisateur_id);

  switch ($post->post_status) {
    case 'pending':
      $preview_url = add_query_arg([
        'preview' => 'true',
        'preview_id' => $post->ID
      ], get_permalink($post));
      wp_safe_redirect($preview_url);
      exit;

    case 'publish':
      wp_safe_redirect(get_permalink($post));
      exit;
  }
}


add_action('wp_ajax_modifier_titre_organisateur', 'modifier_titre_organisateur');
/**
 * 🔹 modifier_titre_organisateur (AJAX)
 *
 * Met à jour dynamiquement le post_title du CPT organisateur de l’utilisateur connecté.
 *
 * - Ne fonctionne que si l’utilisateur est bien l’auteur du CPT
 * - Refuse les titres vides ou les accès croisés
 * - Retourne une réponse JSON avec la nouvelle valeur ou un message d’erreur
 *
 * @hook wp_ajax_modifier_titre_organisateur
 */
function modifier_titre_organisateur()
{
  cat_debug('== FICHIER AJAX ORGANISATEUR CHARGÉ ==');
  cat_debug('== ENTREE AJAX modifier_titre_organisateur ==');

  if (!is_user_logged_in()) {
    wp_send_json_error('non_connecte');
  }

  $user_id = get_current_user_id();
  $titre = sanitize_text_field($_POST['valeur'] ?? '');

  if ($titre === '') {
    wp_send_json_error('titre_vide');
  }

  $organisateur_id = get_organisateur_from_user($user_id);
  if (!$organisateur_id) {
    wp_send_json_error('organisateur_introuvable');
  }

  $auteur = (int) get_post_field('post_author', $organisateur_id);
  if ($auteur !== $user_id) {
    wp_send_json_error('acces_refuse');
  }

  if (!utilisateur_peut_editer_champs($organisateur_id)) {
    wp_send_json_error('acces_refuse');
  }

  $result = wp_update_post([
    'ID'         => $organisateur_id,
    'post_title' => $titre,
  ], true);

  cat_debug("=== DEBUG TITRE ===");
  cat_debug("Résultat : " . print_r($result, true));
  $post = get_post($organisateur_id);
  cat_debug("Titre réel en base : " . $post->post_title);


  cat_debug("=== MODIF ORGANISATEUR ===");
  cat_debug("User ID: " . $user_id);
  cat_debug("Post ID: " . $organisateur_id);
  cat_debug("Titre envoyé : " . $titre);



  if (is_wp_error($result)) {
    wp_send_json_error('echec_mise_a_jour');
  }

  wp_send_json_success([
    'valeur' => $titre,
  ]);
}

/**
 * Retourne la liste complète des types de lien public supportés.
 *
 * Chaque type est représenté par un tableau contenant :
 * - 'label' : Nom lisible du lien (ex : "Site Web", "Discord", ...)
 * - 'icone' : Classe FontAwesome correspondant à l’icône à afficher
 *
 * @return array Liste des types de lien public.
 */
function organisateur_get_liste_liens_publics()
{
  return [
    'site_web' => [
      'label' => 'Site Web',
      'icone' => 'fa-solid fa-globe'
    ],
    'discord' => [
      'label' => 'Discord',
      'icone' => 'fa-brands fa-discord'
    ],
    'facebook' => [
      'label' => 'Facebook',
      'icone' => 'fa-brands fa-facebook-f'
    ],
    'twitter' => [
      'label' => 'Twitter/X',
      'icone' => 'fa-brands fa-x-twitter'
    ],
    'instagram' => [
      'label' => 'Instagram',
      'icone' => 'fa-brands fa-instagram'
    ],
  ];
}

/**
 * Retourne les informations associées à un type de lien public donné.
 *
 * Si le type n’est pas reconnu, un fallback est retourné avec :
 * - Label = ucfirst du type
 * - Icône = fa-solid fa-link
 *
 * @param string $type_de_lien Type de lien à interroger (ex : "discord", "site_web").
 * @return array ['label' => string, 'icone' => string]
 */
function organisateur_get_lien_public_infos($type_de_lien)
{
  $liens = organisateur_get_liste_liens_publics();
  $type = strtolower(trim($type_de_lien));

  return $liens[$type] ?? [
    'label' => ucfirst($type),
    'icone' => 'fa-solid fa-link'
  ];
}



/**
 * Pré-remplit le champ ACF "utilisateurs_associes" avec l'auteur du CPT "organisateur".
 *
 * @param int $post_id ID du post en cours de sauvegarde.
 * @return void
 */
function pre_remplir_utilisateur_associe($post_id)
{
  if (get_post_type($post_id) !== 'organisateur') {
    return;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  $auteur_id = get_post_field('post_author', $post_id);
  if (!$auteur_id) {
    return;
  }

  $utilisateurs_associes = get_post_meta($post_id, 'utilisateurs_associes', true);

  if (empty($utilisateurs_associes) || !is_array($utilisateurs_associes)) {
    // Stocker uniquement l’auteur sous forme de tableau non sérialisé
    update_field('utilisateurs_associes', [strval($auteur_id)], $post_id);
  }
}
add_action('acf/save_post', 'pre_remplir_utilisateur_associe', 20);

/**
 * Valide la longueur minimale de la présentation d'un organisateur.
 */
add_filter('acf/validate_value/name=description_longue', function ($valid, $value, $field, $input) {
    if (!$valid) {
        return $valid;
    }

    if (get_post_type($_POST['post_ID'] ?? 0) !== 'organisateur') {
        return $valid;
    }

    $texte = trim(strip_tags((string) $value));
    if ($texte === '') {
        return __('La présentation ne peut pas être vide.', 'chassesautresor-com');
    }

    if (mb_strlen($texte) < 50) {
        return __('La présentation doit contenir au moins 50 caractères.', 'chassesautresor-com');
    }

    return $valid;
}, 10, 4);
