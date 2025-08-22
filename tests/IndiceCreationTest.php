<?php
namespace {
    if (!defined('TITRE_DEFAUT_INDICE')) {
        define('TITRE_DEFAUT_INDICE', 'indice');
    }

    if (!function_exists('__')) {
        function __($text, $domain = null) { return $text; }
    }

    if (!function_exists('get_post_type')) {
        function get_post_type($id) { global $post_type; return $post_type; }
    }

    if (!function_exists('is_user_logged_in')) {
        function is_user_logged_in() { global $is_logged_in; return $is_logged_in; }
    }

    if (!function_exists('utilisateur_peut_modifier_post')) {
        function utilisateur_peut_modifier_post($id) { global $can_edit; return $can_edit; }
    }

    if (!function_exists('get_current_user_id')) {
        function get_current_user_id() { return 1; }
    }

    if (!function_exists('get_organisateur_from_chasse')) {
        function get_organisateur_from_chasse($chasse_id) { return 7; }
    }

    if (!class_exists('WP_Error')) {
        class WP_Error {
            private $code;
            private $message;
            public function __construct($code = '', $message = '')
            {
                $this->code    = $code;
                $this->message = $message;
            }
            public function get_error_code() { return $this->code; }
            public function get_error_message() { return $this->message; }
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) { return $thing instanceof WP_Error; }
    }
}

namespace IndiceCreation {

use PHPUnit\Framework\TestCase;

class IndiceCreationTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_error_when_user_not_logged_in(): void
    {
        global $post_type, $is_logged_in, $can_edit;
        $post_type    = 'chasse';
        $is_logged_in = false;
        $can_edit     = true;

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

        $result = \creer_indice_pour_objet(42, 'chasse');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('non_connecte', $result->get_error_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_error_when_target_id_invalid(): void
    {
        global $post_type, $is_logged_in, $can_edit;
        $post_type    = null;
        $is_logged_in = true;
        $can_edit     = true;

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

        $result = \creer_indice_pour_objet(42, 'chasse');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('cible_invalide', $result->get_error_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_returns_error_when_user_lacks_permissions(): void
    {
        global $post_type, $is_logged_in, $can_edit;
        $post_type    = 'chasse';
        $is_logged_in = true;
        $can_edit     = false;

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

        $result = \creer_indice_pour_objet(42, 'chasse');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('permission_refusee', $result->get_error_code());
    }
}

}
