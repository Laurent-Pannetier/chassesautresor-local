<?php

declare(strict_types=1);

/** Business role identifiers must remain available independently of the active theme. */
if (!defined('ROLE_ORGANISATEUR')) {
    define('ROLE_ORGANISATEUR', 'organisateur');
}
if (!defined('ROLE_ORGANISATEUR_CREATION')) {
    define('ROLE_ORGANISATEUR_CREATION', 'organisateur_creation');
}

/** Solution lifecycle states shared by the plugin and presentation adapters. */
foreach ([
    'SOLUTION_STATE_INVALIDE' => 'INVALIDE',
    'SOLUTION_STATE_FIN_CHASSE' => 'FIN_CHASSE',
    'SOLUTION_STATE_FIN_CHASSE_DIFFERE' => 'FIN_CHASSE_DIFFERE',
    'SOLUTION_STATE_A_VENIR' => 'A_VENIR',
    'SOLUTION_STATE_EN_COURS' => 'EN_COURS',
    'SOLUTION_STATE_DESACTIVE' => 'DESACTIVE',
] as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}

if (!defined('CAT_DEBUG_VERBOSE')) {
    define('CAT_DEBUG_VERBOSE', false);
}
