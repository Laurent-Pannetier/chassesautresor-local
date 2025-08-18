<?php
// 🔒 Vérification minimale
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    exit('ID manquant ou invalide');
}

$image_id = (int) $_GET['id'];
$taille   = $_GET['taille'] ?? 'full';

// 🔁 Chargement des fonctions
if (!function_exists('trouver_chemin_image')) {
    require_once get_stylesheet_directory() . '/inc/enigme-functions.php';
}
if (!function_exists('utilisateur_peut_voir_enigme')) {
    require_once get_stylesheet_directory() . '/inc/statut-functions.php';
}

// 🧩 Récupération de l'énigme associée à cette image
$parent_id = wp_get_post_parent_id($image_id);
if (!$parent_id || get_post_type($parent_id) !== 'enigme') {
    http_response_code(403);
    exit('Image non autorisée');
}

// 🔐 Vérification d'accès
if (!utilisateur_peut_voir_enigme($parent_id)) {
    http_response_code(403);
    exit('Accès refusé');
}

// 📦 Récupération du chemin de l'image
$info = trouver_chemin_image($image_id, $taille);
$path = $info['path'] ?? null;
$mime = $info['mime'] ?? 'application/octet-stream';

// 🔁 Fallback automatique vers full si fichier manquant
if (!$path && $taille !== 'full') {
    $info = trouver_chemin_image($image_id, 'full');
    $path = $info['path'] ?? null;
    $mime = $info['mime'] ?? 'application/octet-stream';
}

if (!$path) {
    http_response_code(404);
    exit('Fichier introuvable');
}

// 🧹 Nettoyage WordPress
ob_clean();
header_remove();
remove_all_actions('shutdown');
remove_all_actions('template_redirect');

// ✅ Envoi du fichier
// 📅 Cache
$mtime = filemtime($path);
$etag  = '"' . md5($mtime . filesize($path)) . '"';

header('Cache-Control: private, max-age=86400');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);

$if_none_match    = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

if (($if_none_match && trim($if_none_match) === $etag) ||
    ($if_modified_since && strtotime($if_modified_since) >= $mtime)) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
readfile($path);
exit;

