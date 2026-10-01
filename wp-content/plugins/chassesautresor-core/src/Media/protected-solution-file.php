<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedSolutionAssetService;

$log = static function (string $message): void {
    error_log('[protected-solution-file] ' . $message);
};

$userId = get_current_user_id();
if ($userId <= 0) {
    $log('Unauthenticated request denied.');
    status_header(403);
    exit(__('Accès refusé : vous devez être connecté.', 'chassesautresor-com'));
}

$targetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$targetType = (string) get_post_type($targetId);
if ($targetId <= 0 || !in_array($targetType, ['enigme', 'chasse'], true)) {
    $log('Unsupported target requested.');
    status_header(404);
    exit(__('Fichier introuvable.', 'chassesautresor-com'));
}

$assets = new ProtectedSolutionAssetService();
$solution = $assets->findSolution($targetId, $targetType);
if ($solution === null) {
    $log('No active solution found for target ' . $targetId . '.');
    status_header(404);
    exit(__('Solution introuvable.', 'chassesautresor-com'));
}

if (!$assets->canView($targetId, $targetType, $userId)) {
    $log('User denied for target ' . $targetId . '.');
    status_header(403);
    exit(__('Accès non autorisé à cette solution.', 'chassesautresor-com'));
}

$path = $assets->findFilePath($solution);
if ($path === null || !is_file($path)) {
    $log('Attachment missing for target ' . $targetId . '.');
    status_header(404);
    exit(__('Fichier de solution introuvable.', 'chassesautresor-com'));
}

if (!is_readable($path)) {
    $log('Attachment unreadable for target ' . $targetId . '.');
    status_header(403);
    exit(__('Fichier de solution illisible.', 'chassesautresor-com'));
}

$filename = sanitize_file_name(basename($path));
$filesize = filesize($path);
if ($filesize === false) {
    status_header(500);
    exit(__('Impossible de lire le fichier de solution.', 'chassesautresor-com'));
}

nocache_headers();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
readfile($path);
exit;
