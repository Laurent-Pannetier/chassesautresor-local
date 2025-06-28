<?php
/**
 * Autoload theme helper files.
 */
defined('ABSPATH') || exit;

$base = __DIR__ . '/';
$files = [
    'constants.php',
    'utils.php',
    'shortcodes-init.php',
    'enigme-functions.php',
    'user-functions.php',
    'chasse-functions.php',
    'gamify-functions.php',
    'utils/titres.php',
    'statut-functions.php',
    'admin-functions.php',
    'organisateur-functions.php',
    'access-functions.php',
    'relations-functions.php',
    'layout-functions.php',
    'utils/liens.php',
    'edition/edition-core.php',
    'edition/edition-organisateur.php',
    'edition/edition-chasse.php',
    'edition/edition-enigme.php',
    'edition/edition-securite.php',
];

foreach ($files as $file) {
    $path = $base . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}
