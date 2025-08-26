<?php
namespace {
    if (!function_exists('get_post_type')) {
        function get_post_type($id)
        {
            return 'indice';
        }
    }

    if (!function_exists('get_posts')) {
        function get_posts($args)
        {
            return [];
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

    if (!function_exists('get_post')) {
        function get_post($post_id)
        {
            return (object) [
                'post_date'     => '2024-01-01 00:00:00',
                'post_date_gmt' => '2024-01-01 00:00:00',
            ];
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

    if (!function_exists('recuperer_id_chasse_associee')) {
        function recuperer_id_chasse_associee(int $enigme_id): int
        {
            return 42;
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

        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_completes_missing_chasse_link_from_target(): void
        {
            global $fields, $updated_fields;
            $post_id = 456;
            $fields  = [
                $post_id => [
                    'indice_cible_type'    => 'enigme',
                    'indice_enigme_linked' => 789,
                    'indice_contenu'       => '',
                    'indice_image'         => null,
                ],
            ];

            require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-indice.php';

            \mettre_a_jour_cache_indice($post_id);

            $this->assertSame(42, $updated_fields['indice_chasse_linked']);
        }
    }
}

