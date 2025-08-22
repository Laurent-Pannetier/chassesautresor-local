<?php
namespace CreerIndice { 
    if (!defined('TITRE_DEFAUT_INDICE')) {
        define('TITRE_DEFAUT_INDICE', 'indice');
    }

    require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

    class WP_Error
    {
        private $message;
        public function __construct($code = '', $message = '')
        {
            $this->message = $message;
        }
        public function get_error_message()
        {
            return $this->message;
        }
    }

    function get_post_type($id)
    {
        return 'chasse';
    }

    function is_user_logged_in()
    {
        global $is_logged_in;
        return $is_logged_in;
    }

    function get_current_user_id()
    {
        return 1;
    }

    function utilisateur_peut_modifier_post($id)
    {
        global $can_edit;
        return $can_edit;
    }

    function wp_insert_post($args)
    {
        return 123;
    }

    function update_field($field, $value, $post_id)
    {
        global $updated_fields;
        $updated_fields[$field] = $value;
    }

    function current_time($format)
    {
        return '2024-01-01 00:00:00';
    }

    function is_wp_error($thing)
    {
        return $thing instanceof WP_Error;
    }
}

namespace CreerIndice {

use PHPUnit\Framework\TestCase;

class CreerIndicePermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        global $is_logged_in, $can_edit;
        $is_logged_in = true;
        $can_edit = false;
    }

    protected function tearDown(): void
    {
        global $is_logged_in, $can_edit;
        $is_logged_in = true;
        $can_edit = false;
    }

    public function test_creates_indice_when_authorised(): void
    {
        global $can_edit, $updated_fields;
        $can_edit = true;
        $updated_fields = [];

        $result = creer_indice_pour_objet(42, 'chasse');
        $this->assertSame(123, $result);
        $this->assertSame('chasse', $updated_fields['indice_cible']);
    }
}
}
