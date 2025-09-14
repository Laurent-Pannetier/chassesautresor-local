<?php
namespace {
    if (!function_exists('esc_html__')) {
        function esc_html__($text, $domain = null) { return $text; }
    }
    if (!function_exists('esc_attr__')) {
        function esc_attr__($text, $domain = null) { return $text; }
    }
    if (!function_exists('esc_html')) {
        function esc_html($text) { return $text; }
    }
    if (!function_exists('esc_attr')) {
        function esc_attr($text) { return $text; }
    }
    if (!function_exists('esc_url')) {
        function esc_url($url) { return $url; }
    }
    if (!function_exists('wp_strip_all_tags')) {
        function wp_strip_all_tags($text) { return $text; }
    }
    if (!function_exists('get_field')) {
        function get_field($key, $post_id) {
            global $fields;
            return $fields[$key] ?? '';
        }
    }
    if (!function_exists('get_the_title')) {
        function get_the_title($id) { return 'Titre'; }
    }
    if (!function_exists('get_permalink')) {
        function get_permalink($id) {
            if (is_object($id) && isset($id->ID)) {
                $id = $id->ID;
            }
            return 'https://example.com/' . $id;
        }
    }
    if (!function_exists('wp_get_attachment_image')) {
        function wp_get_attachment_image($id, $size) { return ''; }
    }
    if (!function_exists('cta_render_proposition_cell')) {
        function cta_render_proposition_cell($text) { return ''; }
    }
    if (!function_exists('cta_render_pager')) {
        function cta_render_pager($page, $pages, $class = '') { return ''; }
    }
    if (!function_exists('mysql2date')) {
        function mysql2date($format, $date) { return $date; }
    }
    if (!function_exists('__')) {
        function __($text, $domain = null) { return $text; }
    }
    if (!function_exists('convertir_en_datetime')) {
        function convertir_en_datetime(?string $date_string, array $formats = ['d/m/Y g:i a', 'Y-m-d H:i:s', 'Y-m-d\\TH:i']): ?\DateTime
        {
            if (empty($date_string)) {
                return null;
            }
            foreach ($formats as $format) {
                $dt = \DateTime::createFromFormat($format, $date_string);
                if ($dt) {
                    return $dt;
                }
            }
            return null;
        }
    }
    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key, $single = false)
        {
            global $post_meta;
            return $post_meta[$post_id][$key] ?? '';
        }
    }
    if (!function_exists('get_indice_title')) {
        function get_indice_title($post)
        {
            return get_the_title(is_object($post) ? $post->ID : $post);
        }
    }
}

namespace IndiceDatePrefill {
    use PHPUnit\Framework\TestCase;

    class IndiceDatePrefillTest extends TestCase
    {
        /**
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         */
        public function test_date_is_prefilled_for_editing(): void
        {
            global $fields;
            $fields = [
                'indice_image' => 0,
                'indice_contenu' => '',
                'indice_disponibilite' => 'differe',
                'indice_date_disponibilite' => '14/03/2024 6:00 pm',
                'indice_cache_etat_systeme' => 'accessible',
                'indice_cible_type' => 'chasse',
                'indice_chasse_linked' => 10,
            ];

            $indices = [
                (object) [
                    'ID' => 123,
                    'post_date' => '2024-03-10 00:00:00',
                ],
            ];
            $page = 1;
            $pages = 1;
            $objet_type = 'chasse';
            $objet_id = 10;
            $img_url = '';

            global $post_meta;
            $post_meta = [123 => ['indice_rank' => 1]];
            ob_start();
            require __DIR__ . '/../wp-content/themes/chassesautresor/template-parts/common/indices-table.php';
            $output = ob_get_clean();

            $this->assertStringContainsString('data-indice-date="2024-03-14T18:00"', $output);
            $this->assertStringContainsString('data-indice-rang="1"', $output);
            $this->assertStringContainsString('Titre', $output);
        }
    }
}
