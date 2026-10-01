<?php
defined('ABSPATH') || exit;





function cat_get_hunt_riddle_query_service(): ChassesAuTresor\Core\Relationships\HuntRiddleQueryService
{
    return new ChassesAuTresor\Core\Relationships\HuntRiddleQueryService();
}

function cat_get_organizer_hunt_query_service(): ChassesAuTresor\Core\Relationships\OrganizerHuntQueryService
{
    return new ChassesAuTresor\Core\Relationships\OrganizerHuntQueryService();
}

function cat_get_hunt_management_service(): ChassesAuTresor\Core\Content\HuntManagementService
{
    return new ChassesAuTresor\Core\Content\HuntManagementService();
}

function cat_get_hunt_riddle_cache_service(): ChassesAuTresor\Core\Relationships\HuntRiddleCacheService
{
    return new ChassesAuTresor\Core\Relationships\HuntRiddleCacheService();
}

function cat_get_hunt_feature_service(): ChassesAuTresor\Core\Content\HuntFeatureService
{
    return new ChassesAuTresor\Core\Content\HuntFeatureService();
}

// 📚 SOMMAIRE DU FICHIER : relations-functions.php
//  📦 RÉCUPÉRATION CPT ORGANISATEUR
//  📦 RÉCUPÉRATION CPT CHASSE
//  📦 RÉCUPÉRATION CPT ÉNIGME
//  📦 RECUPERATION TROPHEE
//  📦 ASSIGNATION AUTOMATIQUES
//  🔁 SYNCHRONISATION CHASSE ↔ ÉNIGMES



// ==================================================
// 📦 RÉCUPÉRATION CPT ORGANISATEUR
// ==================================================













// ==================================================
// 📦 RÉCUPÉRATION CPT CHASSE
// ==================================================
/**
 * 🔹 recuperer_chasse_associee → Récupérer la chasse associée à une énigme.
 * 🔹 recuperer_id_chasse_associee → Récupérer l’ID de la chasse associée à une énigme.
 * 🔹 organisateur_a_des_chasses → Vérifier si un organisateur a au moins une chasse associée.
 * 🔹 get_chasses_de_organisateur → Récupérer les chasses associées à un organisateur.
 * 🔹 get_chasses_en_creation() → Récupère les chasses d’un organisateur en cours de création (statuts spécifiques).
 */

/**
 * Récupère la chasse associée à une énigme.
 *
 * @param int $enigme_id ID de l'énigme.
 * @return WP_Post|null Chasse associée ou null si aucune trouvée.
 */
function recuperer_chasse_associee($enigme_id)
{
  $chasse = get_field('chasse_associee', $enigme_id);

  // 📌 ACF peut retourner un tableau (relation multiple) ou un objet unique
  if (is_array($chasse) && !empty($chasse)) {
    return get_post($chasse[0]);
  } elseif ($chasse instanceof WP_Post) {
    return $chasse;
  }

  return null;
}

/**
 * Récupère l'ID de la chasse associée à une énigme.
 *
 * @param int|null $post_id ID du post énigme.
 * @return int|null ID de la chasse ou null si non trouvé.
 */
function recuperer_id_chasse_associee($post_id = null)
{
  static $cached_chasse_id = null;

  if ($cached_chasse_id !== null && $cached_chasse_id > 0) {
    return $cached_chasse_id;
  }

  // 🔹 Lecture du champ ACF
  if ($post_id) {
    $champ = get_field('enigme_chasse_associee', $post_id);

    if (is_array($champ)) {
      $chasse_id = is_object($champ[0]) ? (int) $champ[0]->ID : (int) $champ[0];
    } elseif (is_object($champ)) {
      $chasse_id = (int) $champ->ID;
    } else {
      $chasse_id = (int) $champ;
    }

    if ($chasse_id > 0) {
      return $cached_chasse_id = $chasse_id;
    }
  }

  return null;
}


/**
 * Vérifie si un organisateur a au moins une chasse associée.
 *
 * @param int $organisateur_id ID de l'organisateur.
 * @return bool True si au moins une chasse existe, False sinon.
 */
function organisateur_a_des_chasses($organisateur_id)
{
    $organisateur_id = (int) $organisateur_id;
    if ($organisateur_id <= 0) {
        return false;
    }

    $query = new WP_Query(
        cat_get_organizer_hunt_query_service()->getExistingHuntQueryArgs($organisateur_id)
    );

    return $query->have_posts();
}

/**
 * Vérifie si un organisateur possède au moins une chasse en attente (status "pending").
 *
 * @param int $organisateur_id ID de l'organisateur.
 * @return bool True si une chasse en attente existe, False sinon.
 */
function organisateur_a_chasse_pending(int $organisateur_id): bool
{
    if ($organisateur_id <= 0) {
        return false;
    }

    $query = new WP_Query(
        cat_get_organizer_hunt_query_service()->getExistingHuntQueryArgs($organisateur_id, true)
    );

    return $query->have_posts();
}

/**
 * Récupère les chasses associées à un organisateur.
 *
 * @param int $organisateur_id ID de l'organisateur.
 * @return WP_Query Objet WP_Query contenant les chasses associées.
 */
function get_chasses_de_organisateur($organisateur_id)
{
    static $cache = [];

    $organisateur_id = (int) $organisateur_id;
    if ($organisateur_id <= 0) {
        return new WP_Query();
    }

    if (isset($cache[$organisateur_id])) {
        return $cache[$organisateur_id];
    }

    $query = new WP_Query(
        cat_get_organizer_hunt_query_service()->getHuntIdsQueryArgs($organisateur_id)
    );

    $cache[$organisateur_id] = $query;

    return $query;
}

/**
 * Retourne le nombre de chasses publiées pour un organisateur donné.
 *
 * Utilise un cache statique pour éviter des requêtes répétées.
 *
 * @param int $organisateur_id ID de l'organisateur.
 * @return int Nombre de chasses publiées.
 */
function organisateur_get_nb_chasses_publiees(int $organisateur_id): int
{
    static $cache = [];

    if (isset($cache[$organisateur_id])) {
        return $cache[$organisateur_id];
    }

    if ($organisateur_id <= 0) {
        return $cache[$organisateur_id] = 0;
    }

    $query = new WP_Query(
        cat_get_organizer_hunt_query_service()->getPublishedHuntCountQueryArgs($organisateur_id)
    );

    $count = (int) $query->found_posts;
    $cache[$organisateur_id] = $count;

    return $count;
}

/**
 * 🔹 get_chasses_en_creation() → Récupère les chasses en création pour un organisateur donné.
 *
 * @param int $organisateur_id
 * @return int[]
 */
function get_chasses_en_creation($organisateur_id)
{
    if (!is_numeric($organisateur_id)) {
        cat_debug("⛔ get_chasses_en_creation : ID non numérique : " . print_r($organisateur_id, true));
        return [];
    }

    $chasses_query = get_chasses_de_organisateur($organisateur_id);
    $chasse_ids = is_a($chasses_query, 'WP_Query') ? $chasses_query->posts : (array) $chasses_query;

    if (empty($chasse_ids)) {
        cat_debug("🔍 Aucune chasse liée à l’organisateur $organisateur_id");
        return [];
    }

    $service = cat_get_hunt_management_service();
    $filtered = array_filter($chasse_ids, function ($id) use ($service): bool {
        $id = (int) $id;
        $publicationStatus = (string) get_post_status($id);
        $validationStatus = (string) get_field('chasse_cache_statut_validation', $id);
        $businessStatus = (string) get_field('chasse_cache_statut', $id);

        cat_debug(
            "🧪 #$id | statut=$publicationStatus | validation=$validationStatus | metier=$businessStatus"
        );

        return $service->isInCreation($publicationStatus, $validationStatus, $businessStatus);
    });

    cat_debug("📦 Chasses en création retrouvées : " . count($filtered));

    return array_values($filtered);
}


// ==================================================
//  📦 RÉCUPÉRATION CPT ÉNIGME
// ==================================================
/**
 * 🔹 recuperer_enigmes_associees() → Récupère les énigmes associées à une chasse.
 * 🔹 recuperer_enigmes_pour_chasse() → Retourne la liste des énigmes liées à une chasse via WP_Query.
 * 🔹 recuperer_ids_enigmes_pour_chasse() → Retourne les IDs des énigmes liées à une chasse (requête directe).
 */

/**
 * 🔍 Récupère les énigmes associées à une chasse via le champ ACF `chasse_cache_enigmes`.
 *
 * Gère proprement les cas où ACF retourne des objets ou des IDs.
 * Ajoute des logs de débogage si des doublons sont présents.
 *
 * @param int $chasse_id ID de la chasse.
 * @return array Liste unique d’IDs d’énigmes (int).
 */
function recuperer_enigmes_associees(int $chasse_id): array
{
    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        cat_debug("❌ [recuperer_enigmes_associees] Appel invalide pour ID $chasse_id");
        return [];
    }

    $rawRelationships = get_field('chasse_cache_enigmes', $chasse_id);
    $ids = cat_get_relationship_service()->normalizeIds(
        is_array($rawRelationships) ? $rawRelationships : []
    );
    $uniqueIds = array_values(array_unique($ids));

    if (count($ids) !== count($uniqueIds)) {
        cat_debug("⚠️ [recuperer_enigmes_associees] Doublons détectés pour la chasse #$chasse_id");
    }

    return array_values(array_filter($uniqueIds, function (int $id): bool {
        return get_post_type($id) === 'enigme';
    }));
}


/** *
 * ⚠️ Contrairement à `chasse_cache_enigmes`, cette fonction interroge la base en direct.
 *
 * @param int $chasse_id
 * @return WP_Post[] Liste d’objets WP_Post
 */
function recuperer_enigmes_pour_chasse(int $chasse_id): array
{
    if ($chasse_id <= 0 || get_post_type($chasse_id) !== 'chasse') {
        return [];
    }

    $query = new WP_Query(
        cat_get_hunt_riddle_query_service()->getVisibleRiddlesQueryArgs($chasse_id)
    );

    return $query->have_posts() ? $query->posts : [];
}


/**
 * @param int $chasse_id
 * @return int[] Liste d’IDs (int)
 */
function recuperer_ids_enigmes_pour_chasse(int $chasse_id): array
{
    if ($chasse_id <= 0 || get_post_type($chasse_id) !== 'chasse') {
        return [];
    }

    $query = new WP_Query(
        cat_get_hunt_riddle_query_service()->getRiddleIdsQueryArgs($chasse_id)
    );

    return $query->posts;
}


/**
 * Compatibility wrapper for callers that explicitly refresh hunt feature flags.
 */
function recalculate_chasse_cached_flags(int $chasse_id): void
{
    (new ChassesAuTresor\Core\Content\HuntFeatureCacheManager())->recalculate($chasse_id);
}


// ==================================================
// 🔁 SYNCHRONISATION CHASSE ↔ ÉNIGMES
// ==================================================

function cat_get_hunt_riddle_cache_synchronizer(): ChassesAuTresor\Core\Relationships\HuntRiddleCacheSynchronizer
{
    return new ChassesAuTresor\Core\Relationships\HuntRiddleCacheSynchronizer(
        cat_get_hunt_riddle_cache_service(),
        cat_get_hunt_riddle_query_service(),
        cat_get_relationship_service()
    );
}

/** @return array<string, mixed> */
function synchroniser_cache_enigmes_chasse($chasse_id, $forcer_recalcul = false, $nettoyer_cache = false)
{
    return cat_get_hunt_riddle_cache_synchronizer()->synchronize(
        (int) $chasse_id,
        (bool) $forcer_recalcul,
        (bool) $nettoyer_cache
    );
}

/** @return array<string, mixed> */
function verifier_chasse_cache_enigmes($chasse_id, $mettre_a_jour = false)
{
    return cat_get_hunt_riddle_cache_synchronizer()->compareReality(
        (int) $chasse_id,
        (bool) $mettre_a_jour
    );
}

/** @return array<string, mixed> */
function verifier_cache_chasse_enigmes_valides($chasse_id, $retirer_si_invalide = false)
{
    return cat_get_hunt_riddle_cache_synchronizer()->validateCache(
        (int) $chasse_id,
        (bool) $retirer_si_invalide
    );
}

function synchroniser_relations_cache_enigmes(int $chasse_id): bool
{
    return cat_get_hunt_riddle_cache_synchronizer()->synchronizeRelations($chasse_id);
}

function forcer_relation_enigme_dans_chasse_si_absente(int $enigme_id): void
{
    cat_get_hunt_riddle_cache_synchronizer()->ensureRiddleCached($enigme_id);
}

function verifier_et_synchroniser_cache_enigmes_si_autorise(int $chasse_id): void
{
    $authorized = current_user_can('administrator')
        || current_user_can(ROLE_ORGANISATEUR)
        || current_user_can(ROLE_ORGANISATEUR_CREATION);
    cat_get_hunt_riddle_cache_synchronizer()->maybeSynchronize($chasse_id, $authorized);
}
