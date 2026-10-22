<?php
// 🔒 Vérification minimale
$refuse_image = static function (int $status, string $message): void {
    http_response_code($status);
    do_action('litespeed_control_set_nocache', 'protected riddle image denied');
    header('Cache-Control: private, max-age=0, must-revalidate', true);
    header('X-LiteSpeed-Cache-Control: no-cache', true);
    exit($message);
};

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $refuse_image(400, __('ID manquant ou invalide', 'chassesautresor-com'));
}

$image_id = (int) $_GET['id'];
$taille   = $_GET['taille'] ?? 'full';

$sizes = function_exists('get_intermediate_image_sizes')
    ? get_intermediate_image_sizes()
    : ['thumbnail', 'medium', 'large'];
$sizes[] = 'full';

if (!in_array($taille, $sizes, true)) {
    $refuse_image(400, __('Taille d\'image invalide', 'chassesautresor-com'));
}

$signer = new ChassesAuTresor\Core\Media\ProtectedRiddleImageSignedUrlService();
$exp = isset($_GET['exp']) && ctype_digit((string) $_GET['exp']) ? (int) $_GET['exp'] : 0;
$uid = isset($_GET['uid']) && ctype_digit((string) $_GET['uid']) ? (int) $_GET['uid'] : -1;
$sig = isset($_GET['sig']) ? (string) $_GET['sig'] : '';
$format = isset($_GET['fmt']) ? strtolower((string) $_GET['fmt']) : '';
$current_user_id = get_current_user_id();

if ($format !== '' && $format !== 'webp') {
    $refuse_image(400, __('Format d\'image invalide', 'chassesautresor-com'));
}

// The signature stays tied to the viewer embedded in the URL. A shared cache of
// the homepage can replay that URL for someone else; access is checked next
// for the current session, so a won hunt does not blank every sommaire vignette.
$signature_policy = new ChassesAuTresor\Core\Media\ProtectedRiddleImageRequestPolicy();
$signature_valid = $signer->verify($image_id, $taille, $uid, $exp, $sig, null, $format);
if (!$signature_policy->acceptsSignature($signature_valid, $uid, $current_user_id)) {
    $refuse_image(403, __('Lien image invalide ou expiré', 'chassesautresor-com'));
}

// 🧩 Récupération de l'énigme / étape associée à cette image
global $wpdb;
$image_service = ChassesAuTresor\Core\Support\CoreServiceFactory::riddleImages($wpdb);
$asset_service = new ChassesAuTresor\Core\Media\ProtectedRiddleAssetService();
$step_image_service = ChassesAuTresor\Core\Support\CoreServiceFactory::riddleStepImages($wpdb);
$enigme_id = $image_service->findRiddleId($image_id);
$step_context = $step_image_service->findContext($image_id);

if (!$enigme_id && !$step_context) {
    $refuse_image(403, __('Image non autorisée', 'chassesautresor-com'));
}

// 🔐 Vérification d'accès — une image peut être liée à une énigme ET/OU une étape ;
// on autorise dès qu’un des deux chemins est valide (évite un faux positif
// enigme_visuel_image qui bloquerait une image d’étape pourtant débloquée).
$can_view = false;
if ($enigme_id) {
    $can_view = $asset_service->canViewRiddle($enigme_id, $current_user_id);
}
if (!$can_view && $step_context) {
    $can_view = $step_image_service->canView($image_id, $current_user_id);
}
if (!$can_view) {
    $refuse_image(403, __('Accès refusé', 'chassesautresor-com'));
}

// 📦 Récupération du chemin de l'image
$info = $asset_service->findImage($image_id, $taille, $format);
$path = $info['path'] ?? null;
$mime = $info['mime'] ?? 'application/octet-stream';

// 🔁 Fallback automatique vers full si fichier manquant
if (!$path && $taille !== 'full') {
    $info = $asset_service->findImage($image_id, 'full', $format);
    $path = $info['path'] ?? null;
    $mime = $info['mime'] ?? 'application/octet-stream';
}

if (!$path) {
    $refuse_image(404, __('Fichier introuvable', 'chassesautresor-com'));
}

$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed_mimes, true)) {
    $refuse_image(415, __('Format d\'image invalide', 'chassesautresor-com'));
}

$delivery = new ChassesAuTresor\Core\Media\ProtectedRiddleImageDeliveryService();
$safe_path = is_string($path) ? $delivery->confineToUploads($path) : null;
if ($safe_path === null) {
    $refuse_image(404, __('Fichier introuvable', 'chassesautresor-com'));
}
$path = $safe_path;

// 🧹 Nettoyage WordPress
while (ob_get_level()) {
    ob_end_clean();
}
remove_all_actions('template_redirect');
do_action('litespeed_control_set_nocache');

// ✅ Envoi du fichier
// Cache navigateur privé : jamais partagé (CDN / LiteSpeed). L'URL signée est stable
// pendant sa fenêtre, donc un rechargement réutilise le fichier. Au-delà de 15 min
// le navigateur revalide et l'accès est revérifié.
$mtime = filemtime($path);
$etag  = '"' . md5($mtime . filesize($path)) . '"';

$cache_control = 'Cache-Control: ' . $delivery->browserCacheControl($exp);
$expires = 'Expires: ' . $delivery->browserExpires($exp);
$apply_private_cache = static function () use ($cache_control, $expires): void {
    header('X-LiteSpeed-Cache-Control: no-cache', true);
    header($cache_control, true);
    header($expires, true);
    header_remove('Pragma');
};
$apply_private_cache();
// Réappliqué au moment de l'envoi : un plugin (LiteSpeed, page connectée) peut
// encore remplacer ces en-têtes pendant le shutdown.
if (function_exists('header_register_callback')) {
    header_register_callback($apply_private_cache);
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cross-Origin-Resource-Policy: same-origin');
header("Content-Security-Policy: default-src 'none'; sandbox");
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);

$if_none_match           = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$if_modified_since       = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
$if_none_match_match     = $if_none_match && trim($if_none_match) === $etag;
$if_modified_since_match = $if_modified_since && strtotime($if_modified_since) >= $mtime;

if ($if_none_match_match || $if_modified_since_match) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $mime);

if ($delivery->tryLiteSpeedSend($path, $mime)) {
    exit;
}

header('Content-Length: ' . filesize($path));
readfile($path);
exit;
