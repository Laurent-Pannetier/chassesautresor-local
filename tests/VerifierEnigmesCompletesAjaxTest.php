<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('recuperer_enigmes_associees')) {
    function recuperer_enigmes_associees($chasse_id) {
        return [1];
    }
}

if (!function_exists('verifier_ou_mettre_a_jour_cache_complet')) {
    function verifier_ou_mettre_a_jour_cache_complet($eid) {}
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        throw new Exception('error');
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        global $json_success_data;
        $json_success_data = $data;
        return $data;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-enigme.php';

class VerifierEnigmesCompletesAjaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        global $json_success_data, $fields, $statuses, $enigme_ids;
        $json_success_data = null;
        $fields = [];
        $statuses = [];
        $enigme_ids = [1];
        $fields[1]['enigme_cache_complet'] = true;
    }

    public function test_includes_can_add_flag_false(): void
    {
        global $fields, $statuses, $json_success_data;
        $chasse_id = 5;
        $fields[$chasse_id] = [
            'chasse_cache_statut_validation' => 'creation',
            'chasse_cache_statut'           => 'revision',
        ];
        $statuses[$chasse_id] = 'publish';
        $_POST['chasse_id'] = $chasse_id;

        verifier_enigmes_completes_ajax();

        $this->assertFalse($json_success_data['can_add']);
    }

    public function test_includes_can_add_flag_true(): void
    {
        global $fields, $statuses, $json_success_data;
        $chasse_id = 6;
        $fields[$chasse_id] = [
            'chasse_cache_statut_validation' => 'creation',
            'chasse_cache_statut'           => 'revision',
        ];
        $statuses[$chasse_id] = 'pending';
        $_POST['chasse_id'] = $chasse_id;

        verifier_enigmes_completes_ajax();

        $this->assertTrue($json_success_data['can_add']);
    }
}
