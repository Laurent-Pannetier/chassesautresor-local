<?php
// 🔒 Vérification minimale
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    exit(__('ID manquant ou invalide', 'chassesautresor-com'));
}

$image_id = (int) $_GET['id'];
$taille   = $_GET['taille'] ?? 'full';

error_log('[voir-image-enigme] handler start for image ' . $image_id);
if (headers_sent($file, $line)) {
    error_log("[voir-image-enigme] headers déjà envoyés ($file:$line)");
}

// 🔁 Chargement des fonctions
if (!function_exists('trouver_chemin_image')) {
    require_once get_stylesheet_directory() . '/inc/enigme-functions.php';
}
if (!function_exists('utilisateur_peut_voir_enigme')) {
    require_once get_stylesheet_directory() . '/inc/access-functions.php';
}

// 🧩 Récupération de l'énigme associée à cette image
global $wpdb;
$enigme_id = 0;

$table = $wpdb->prefix . 'acf_enigme_visuel_image';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
    $enigme_id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM $table WHERE value = %d LIMIT 1",
            $image_id
        )
    );
}

if (!$enigme_id) {
    $search = '%:"' . $wpdb->esc_like((string) $image_id) . '";%';
    $sql    = "SELECT post_id FROM {$wpdb->postmeta} "
        . "WHERE meta_key = 'enigme_visuel_image' AND meta_value LIKE %s LIMIT 1";
    $enigme_id = (int) $wpdb->get_var(
        $wpdb->prepare($sql, $search)
    );
}

if (!$enigme_id) {
    http_response_code(403);
    exit(__('Image non autorisée', 'chassesautresor-com'));
}

// 🔐 Vérification d'accès
if (!utilisateur_peut_voir_enigme($enigme_id)) {
    http_response_code(403);
    exit(__('Accès refusé', 'chassesautresor-com'));
}

// 📦 Récupération du chemin de l'image
$info = trouver_chemin_image($image_id, $taille);
$path = $info['path'] ?? null;
$mime = $info['mime'] ?? 'application/octet-stream';
error_log('[voir-image-enigme] path=' . var_export($path, true) . ', mime=' . var_export($mime, true));

// 🔁 Fallback automatique vers full si fichier manquant
if (!$path && $taille !== 'full') {
    $info = trouver_chemin_image($image_id, 'full');
    $path = $info['path'] ?? null;
    $mime = $info['mime'] ?? 'application/octet-stream';
    error_log('[voir-image-enigme] path=' . var_export($path, true) . ', mime=' . var_export($mime, true));
}

if (!$path) {
    http_response_code(404);
    exit(__('Fichier introuvable', 'chassesautresor-com'));
}

// 🧹 Nettoyage WordPress
ob_clean();
header_remove();
remove_all_actions('shutdown');
remove_all_actions('template_redirect');
do_action('litespeed_control_set_nocache');

// ✅ Envoi du fichier
// 📅 Cache (compatible CDN)
$mtime = filemtime($path);
$etag  = '"' . md5($mtime . filesize($path)) . '"';

header('Cache-Control: public, max-age=3600, immutable');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);

$if_none_match    = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

if (($if_none_match && trim($if_none_match) === $etag) ||
    ($if_modified_since && strtotime($if_modified_since) >= $mtime)) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[voir-image-enigme] 304 not modified for image ' . $image_id);
    }
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
$bytes = readfile($path);
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[voir-image-enigme] readfile(' . $path . ') => ' . var_export($bytes, true));
}
exit;

