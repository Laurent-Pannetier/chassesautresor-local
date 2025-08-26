<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return true;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($id)
    {
        return $id === 42 ? 'indice' : 'enigme';
    }
}

if (!function_exists('get_field')) {
    function get_field($field, $post_id)
    {
        global $fields;
        return $fields[$field] ?? null;
    }
}

if (!function_exists('indice_action_autorisee')) {
    function indice_action_autorisee($action, $type, $id)
    {
        return true;
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post($id, $force = false)
    {
        return true;
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args)
    {
        global $captured_meta_queries;
        $captured_meta_queries[] = $args['meta_query'];
        return [];
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($args)
    {
        return true;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null): void
    {
        throw new Exception((string) $data);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null)
    {
        global $json_success;
        $json_success = $data;
        return $data;
    }
}

if (!function_exists('recuperer_id_chasse_associee')) {
    function recuperer_id_chasse_associee($enigme_id)
    {
        return 77;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

final class SupprimerIndiceEnigmeAjaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        global $fields, $captured_meta_queries, $json_success;
        $fields = [
            'indice_cible_type'   => 'enigme',
            'indice_enigme_linked' => 55,
            'indice_chasse_linked' => 77,
        ];
        $captured_meta_queries = [];
        $json_success          = null;
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_reorders_chasse_and_enigme_after_deleting_index(): void
    {
        global $captured_meta_queries;
        $_POST['indice_id'] = 42;

        supprimer_indice_ajax();

        $this->assertCount(2, $captured_meta_queries);
        $foundChasse = $foundEnigme = false;
        foreach ($captured_meta_queries as $metaQuery) {
            foreach ($metaQuery as $clause) {
                if ($clause['key'] === 'indice_chasse_linked' && (int) $clause['value'] === 77) {
                    $foundChasse = true;
                }
                if ($clause['key'] === 'indice_enigme_linked' && (int) $clause['value'] === 55) {
                    $foundEnigme = true;
                }
            }
        }

        $this->assertTrue($foundChasse);
        $this->assertTrue($foundEnigme);
    }
}

