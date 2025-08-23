<?php
namespace {
    if (!function_exists('get_post_type')) {
        function get_post_type($id)
        {
            return 'indice';
        }
    }

    if (!function_exists('wp_is_post_revision')) {
        function wp_is_post_revision($id)
        {
            return false;
        }
    }

    if (!function_exists('wp_is_post_autosave')) {
        function wp_is_post_autosave($id)
        {
            return false;
        }
    }

    if (!function_exists('get_field')) {
        function get_field($name, $post_id)
        {
            global $fields;
            return $fields[$post_id][$name] ?? null;
        }
    }

    if (!function_exists('update_field')) {
        function update_field($name, $value, $post_id): void
        {
            global $updated_fields;
            $updated_fields[$name] = $value;
        }
    }

    if (!function_exists('convertir_en_datetime')) {
        function convertir_en_datetime($date_raw)
        {
            return null;
        }
    }

    if (!function_exists('get_post_status')) {
        function get_post_status($post_id)
        {
            return 'draft';
        }
    }

    if (!function_exists('wp_update_post')) {
        function wp_update_post(array $data): void
        {
            global $updated_post;
            $updated_post = $data;
        }
    }
}

namespace MettreAJourCacheIndiceTest {
    use PHPUnit\Framework\TestCase;

    class MettreAJourCacheIndiceTest extends TestCase
    {
        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_forces_cache_and_state_when_date_missing_or_invalid(): void
        {
            global $fields, $updated_fields;
            $post_id = 123;
            $fields  = [
                $post_id => [
                    'indice_contenu'            => 'foo',
                    'indice_image'              => null,
                    'indice_disponibilite'      => 'differe',
                    'indice_date_disponibilite' => 'invalid',
                ],
            ];

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \mettre_a_jour_cache_indice($post_id);

            $this->assertSame(0, $updated_fields['indice_cache_complet']);
            $this->assertSame('desactive', $updated_fields['indice_cache_etat_systeme']);
        }
    }
}

