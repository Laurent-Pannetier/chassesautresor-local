<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/statut-functions.php';

if (!function_exists('get_post_type')) {
    function get_post_type($id)
    {
        return 'chasse';
    }
}

if (!function_exists('titre_est_valide')) {
    function titre_est_valide($id)
    {
        return true;
    }
}

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse($chasse_id)
    {
        global $enigme_ids;
        return $enigme_ids ?? [];
    }
}

if (!function_exists('get_field')) {
    function get_field($field_name, $post_id)
    {
        global $post_fields;
        return $post_fields[$post_id][$field_name] ?? null;
    }
}

class ChasseCompletenessTest extends TestCase
{
    public function test_chasse_est_complet_returns_false_when_automatic_without_validable_enigme(): void
    {
        global $post_fields, $enigme_ids;
        $chasse_id   = 100;
        $enigme_ids  = [201, 202];
        $post_fields = [
            $chasse_id => [
                'chasse_principale_description' => 'desc',
                'chasse_principale_image'       => ['ID' => 123],
                'chasse_mode_fin'               => 'automatique',
            ],
            201 => ['enigme_mode_validation' => 'aucune'],
            202 => ['enigme_mode_validation' => 'aucune'],
        ];

        $this->assertFalse(chasse_est_complet($chasse_id));
    }
}
