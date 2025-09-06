<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('remove_accents')) {
    function remove_accents($string)
    {
        return $string;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/statut-functions.php';

final class StatutFunctionsTest extends TestCase
{
    public function test_est_enigme_resolue_accepte_terminee(): void
    {
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public function prepare($q, ...$args) { return $q; }
            public function get_var($query) { return 'terminee'; }
        };

        $this->assertTrue(est_enigme_resolue_par_utilisateur(1, 2));
    }
}
