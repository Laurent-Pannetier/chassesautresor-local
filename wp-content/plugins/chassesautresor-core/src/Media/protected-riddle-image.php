<?php
// 🔒 Vérification minimale
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    exit(__('ID manquant ou invalide', 'chassesautresor-com'));
}

$image_id = (int) $_GET['id'];
$taille   = $_GET['taille'] ?? 'full';

$sizes = function_exists('get_intermediate_image_sizes')
    ? get_intermediate_image_sizes()
    : ['thumbnail', 'medium', 'large'];
$sizes[] = 'full';

if (!in_array($taille, $sizes, true)) {
    http_response_code(400);
    exit(__('Taille d\'image invalide', 'chassesautresor-com'));
}

// 🧩 Récupération de l'énigme associée à cette image
global $wpdb;
$image_service = ChassesAuTresor\Core\Support\CoreServiceFactory::riddleImages($wpdb);
$asset_service = new ChassesAuTresor\Core\Media\ProtectedRiddleAssetService();
$enigme_id = $image_service->findRiddleId($image_id);
$step_image_service = ChassesAuTresor\Core\Support\CoreServiceFactory::riddleStepImages($wpdb);
$step_context = $enigme_id ? null : $step_image_service->findContext($image_id);

if (!$enigme_id && !$step_context) {
    http_response_code(403);
    exit(__('Image non autorisée', 'chassesautresor-com'));
}

// 🔐 Vérification d'accès
$can_view = $enigme_id
    ? $asset_service->canViewRiddle($enigme_id, get_current_user_id())
    : $step_image_service->canView($image_id, get_current_user_id());
if (!$can_view) {
    http_response_code(403);
    exit(__('Accès refusé', 'chassesautresor-com'));
}

// 📦 Récupération du chemin de l'image
$info = $asset_service->findImage($image_id, $taille);
$path = $info['path'] ?? null;
$mime = $info['mime'] ?? 'application/octet-stream';

// 🔁 Fallback automatique vers full si fichier manquant
if (!$path && $taille !== 'full') {
    $info = $asset_service->findImage($image_id);
    $path = $info['path'] ?? null;
    $mime = $info['mime'] ?? 'application/octet-stream';
}

if (!$path) {
    http_response_code(404);
    exit(__('Fichier introuvable', 'chassesautresor-com'));
}

// 🧹 Nettoyage WordPress
while (ob_get_level()) {
    ob_end_clean();
}
nocache_headers();
remove_all_actions('template_redirect');
do_action('litespeed_control_set_nocache');

// ✅ Envoi du fichier
// 📅 Cache (compatible CDN)
$mtime = filemtime($path);
$etag  = '"' . md5($mtime . filesize($path)) . '"';

header('Cache-Control: private, no-store, max-age=0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);

$if_none_match           = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$if_modified_since       = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
$if_none_match_match     = $if_none_match && trim($if_none_match) === $etag;
$if_modified_since_match = $if_modified_since && strtotime($if_modified_since) >= $mtime;

if ($if_none_match_match || $if_modified_since_match) {
    // Les lignes ci-dessous sont désactivées afin de toujours renvoyer le fichier avec un
    // code 200 et confirmer que le bloc de cache est en cause.
    // http_response_code(304);
    // exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
