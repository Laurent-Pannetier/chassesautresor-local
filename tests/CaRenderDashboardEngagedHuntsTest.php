<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CaRenderDashboardEngagedHuntsTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_player_organizer_can_view_widget(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/fixtures/');
        }

        $_GET = [];

        eval('function is_user_logged_in(){return true;}');
        eval('function wp_get_current_user(){return (object) ["ID" => 42, "roles" => ["subscriber", "organisateur"]];}');
        eval('function current_user_can($cap){return false;}');
        eval('function est_organisateur($user_id){return $user_id === 42;}');
        eval('function apply_filters($tag,...$args){return $args[0] ?? null;}');
        eval('function absint($value){return (int) abs($value);}');
        eval('function wp_enqueue_script(...$args){}');
        eval('function get_stylesheet_directory(){return __DIR__ . "/../wp-content/themes/chassesautresor";}');
        eval('function get_stylesheet_directory_uri(){return "https://example.com/wp-content/themes/chassesautresor";}');
        eval('function esc_html__($text,$domain=null){return $text;}');
        eval('function __($text,$domain=null){return $text;}');
        eval('function admin_url($path=""){return "https://example.com/" . ltrim($path, "/");}');
        eval('function wp_create_nonce($action){return "nonce";}');
        eval('function esc_url($url){return $url;}');
        eval('function esc_attr($text){return $text;}');
        eval('function get_template_part($slug,$name=null,$args=[]) {echo "<div class=\"template-part\">" . $slug . "</div>";}');
        eval('function cta_render_pager($page,$total_pages,$class,$attributes=[]) {return "<nav class=\"" . $class . "\"></nav>";}');

        $wpdb = new class
        {
            public string $prefix = 'wp_';

            public function prepare(string $query, int $user_id): string
            {
                return sprintf($query, $user_id);
            }

            public function get_col(string $query): array
            {
                return [101];
            }
        };

        $GLOBALS['wpdb'] = $wpdb;

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/user-functions.php';

        ob_start();
        ca_render_dashboard_engaged_hunts();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'The dashboard section should render for player organizers.');
        $this->assertStringContainsString('data-engaged-hunts', $output);
        $this->assertStringContainsString('template-part', $output);
    }
}

