<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/statut-functions.php';

if (!function_exists('get_post_type')) {
    function get_post_type($id)
    {
        return 'enigme';
    }
}

if (!function_exists('titre_est_valide')) {
    function titre_est_valide($id)
    {
        return true;
    }
}

if (!function_exists('get_field')) {
    function get_field($field_name, $post_id)
    {
        global $fields;
        return $fields[$post_id][$field_name] ?? null;
    }
}

class EnigmePrerequisCompletionTest extends TestCase
{
    public function test_enigme_incomplete_when_prerequis_required_but_empty(): void
    {
        global $fields;
        $fields = [
            1 => [
                'enigme_visuel_image'      => [['ID' => 123]],
                'enigme_mode_validation'   => 'automatique',
                'enigme_reponse_bonne'     => 'soluce',
                'enigme_acces_condition'   => 'pre_requis',
                'enigme_acces_pre_requis'  => [],
            ],
        ];

        $this->assertFalse(enigme_est_complet(1));
    }
}
