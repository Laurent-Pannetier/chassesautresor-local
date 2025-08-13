<?php
use PHPUnit\Framework\TestCase;

if (!defined('TITRE_DEFAUT_ENIGME')) {
    define('TITRE_DEFAUT_ENIGME', 'enigme');
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-enigme.php';

if (!function_exists('get_post_type')) {
    function get_post_type($id)
    {
        return 'chasse';
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 1;
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($id)
    {
        return (object) ['ID' => $id];
    }
}

if (!function_exists('get_organisateur_from_chasse')) {
    function get_organisateur_from_chasse($chasse_id)
    {
        return 10;
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post($args)
    {
        return 123;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing)
    {
        return false;
    }
}

if (!function_exists('get_option')) {
    function get_option($name)
    {
        return false;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($name)
    {
    }
}

if (!function_exists('update_field')) {
    function update_field($field, $value, $post_id)
    {
        global $updated_fields;
        $updated_fields[$field] = $value;
    }
}

if (!function_exists('enigme_mettre_a_jour_etat_systeme')) {
    function enigme_mettre_a_jour_etat_systeme($id)
    {
    }
}

if (!function_exists('cat_debug')) {
    function cat_debug(...$args)
    {
    }
}

class CreerEnigmeTest extends TestCase
{
    public function test_creer_enigme_sets_mode_validation_to_automatique(): void
    {
        global $updated_fields;
        $updated_fields = [];

        $enigme_id = creer_enigme_pour_chasse(55, 1);

        $this->assertSame('automatique', $updated_fields['enigme_mode_validation']);
        $this->assertSame(123, $enigme_id);
    }
}
