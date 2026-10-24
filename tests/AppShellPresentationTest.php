<?php

use PHPUnit\Framework\TestCase;

class AppShellPresentationTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_team_stats_without_a_hunt_do_not_invent_values(): void
    {
        $this->bootShell();

        ob_start();
        cta_shell_render_team_stats();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Aucune chasse engagée pour le moment.', $html);
        $this->assertStringNotContainsString('Emplacement', $html);
        $this->assertStringNotContainsString('team-stat', $html);
        $text = trim(strip_tags($html));
        $this->assertSame(0, preg_match('/\d/', $text));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hunt_banner_stays_empty_without_a_hunt_image(): void
    {
        $this->bootShell();

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('hunt-banner', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hunt_banner_maps_the_hunt_image_onto_a_sphere(): void
    {
        $this->bootShell();
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $GLOBALS['cat_theme_dir'] = $theme;
        $GLOBALS['cat_enqueued_scripts'] = [];

        eval('function esc_url($url){return $url;}');
        eval('function get_option($name, $default = false){
            return $GLOBALS["cat_options"][$name] ?? $default;
        }');
        $GLOBALS['cat_options'] = ['cta_hunt_banner_sphere_enabled' => '1'];
        eval('function cat_get_primary_hunt_id(){return 7;}');
        eval('function get_the_title($id = 0){return "Antre de feu";}');
        eval('function get_the_post_thumbnail_url($id, $size = "full"){
            return "https://example.test/chasse.jpg";
        }');
        eval('function get_stylesheet_directory(){return $GLOBALS["cat_theme_dir"];}');
        eval('function get_stylesheet_directory_uri(){return "https://example.test/theme";}');
        eval('function wp_enqueue_script($handle, $src = "", $deps = [], $ver = false, $in_footer = false){
            $GLOBALS["cat_enqueued_scripts"][] = [
                "handle" => $handle,
                "src" => $src,
                "footer" => $in_footer,
            ];
        }');

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('data-hunt-sphere', $html);
        $this->assertStringContainsString('data-image="https://example.test/chasse.jpg"', $html);
        $this->assertStringContainsString('hunt-banner__canvas', $html);
        $this->assertStringContainsString('hunt-banner__fallback', $html);
        $this->assertStringContainsString('is-pending', $html);
        $this->assertStringContainsString('alt="Antre de feu"', $html);
        $this->assertStringNotContainsString('hunt-banner__empty', $html);

        $enqueued = $GLOBALS['cat_enqueued_scripts'];
        $this->assertCount(1, $enqueued);
        $this->assertSame('cta-hunt-sphere', $enqueued[0]['handle']);
        $this->assertStringContainsString('/assets/js/hunt-banner-sphere.js', $enqueued[0]['src']);
        $this->assertTrue($enqueued[0]['footer']);

        $source = (string) file_get_contents($theme . '/assets/js/src/hunt-banner-sphere.js');
        $motion = (string) file_get_contents($theme . '/assets/js/src/hunt-banner-sphere-motion.js');
        $scss = (string) file_get_contents($theme . '/assets/scss/_app-shell.scss');
        $this->assertStringContainsString('SphereGeometry', $source);
        $this->assertStringContainsString('prefers-reduced-motion', $source);
        $this->assertStringContainsString('stepSphere', $source);
        $this->assertStringContainsString('travelLimit(viewHalfWidth, camera.position.z)', $source);
        $this->assertStringContainsString('IntersectionObserver', $source);
        $this->assertStringContainsString('--hunt-sphere-x', $source);
        $this->assertStringNotContainsString('kickAngle', $source);
        $this->assertStringNotContainsString('rotation.y += 0.0042', $source);
        $this->assertStringNotContainsString('quaternion.slerp', $source);
        $this->assertStringContainsString('bounced', $motion);
        $this->assertStringContainsString('velocityX', $motion);
        $this->assertStringContainsString('--hunt-sphere-x', $scss);
        $this->assertStringContainsString('--hunt-sphere-shadow', $scss);
        $this->assertStringContainsString('html[data-theme="day"] .hunt-banner__sphere::after', $scss);
        $bundle = (string) file_get_contents($theme . '/assets/js/hunt-banner-sphere.js');
        $this->assertGreaterThan(50000, strlen($bundle));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hunt_banner_serves_a_normal_image_when_the_sphere_is_off(): void
    {
        $this->bootShell();
        $GLOBALS['cat_enqueued_scripts'] = [];
        $GLOBALS['cat_attachment_image'] = null;

        eval('function esc_url($url){return $url;}');
        eval('function cat_get_primary_hunt_id(){return 7;}');
        eval('function get_the_title($id = 0){return "Antre de feu";}');
        eval('function get_field($key, $id){
            return ["ID" => 42, "url" => "https://example.test/chasse.jpg"];
        }');
        eval('function imagify_get_webp_url($url){return "https://example.test/chasse.jpg.webp";}');
        eval('function wp_get_attachment_image($id, $size = "thumbnail", $icon = false, $attr = []){
            $GLOBALS["cat_attachment_image"] = ["id" => $id, "size" => $size, "attr" => $attr];
            $class = isset($attr["class"]) ? $attr["class"] : "";
            $alt = isset($attr["alt"]) ? $attr["alt"] : "";
            return "<img class=\"" . $class . "\" src=\"https://example.test/chasse-1024.jpg\""
                . " srcset=\"https://example.test/chasse-1024.jpg 1024w, https://example.test/chasse.jpg 1600w\""
                . " alt=\"" . $alt . "\" loading=\"eager\" fetchpriority=\"high\">";
        }');
        eval('function wp_enqueue_script($handle, $src = "", $deps = [], $ver = false, $in_footer = false){
            $GLOBALS["cat_enqueued_scripts"][] = $handle;
        }');

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('hunt-banner__frame', $html);
        $this->assertStringContainsString('class="hunt-banner__photo"', $html);
        $this->assertStringContainsString('srcset="https://example.test/chasse-1024.jpg 1024w', $html);
        $this->assertStringContainsString('alt="Antre de feu"', $html);
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
        $this->assertStringNotContainsString('.webp', $html);
        $this->assertStringNotContainsString('hunt-banner__canvas', $html);
        $this->assertStringNotContainsString('data-hunt-sphere', $html);
        $this->assertStringNotContainsString('document.currentScript', $html);
        $this->assertStringNotContainsString('hunt-banner__empty', $html);
        $this->assertSame([], $GLOBALS['cat_enqueued_scripts']);
        $this->assertSame(42, $GLOBALS['cat_attachment_image']['id']);
        $this->assertSame('large', $GLOBALS['cat_attachment_image']['size']);
        $this->assertSame('eager', $GLOBALS['cat_attachment_image']['attr']['loading']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_still_banner_prefers_the_panoramic_field(): void
    {
        $this->bootShell();
        $GLOBALS['cat_attachment_image'] = null;

        eval('function esc_url($url){return $url;}');
        eval('function cat_get_primary_hunt_id(){return 7;}');
        eval('function get_the_title($id = 0){return "Antre de feu";}');
        eval('function get_field($key, $id){
            if ($key === "chasse_bandeau_accueil") {
                return ["ID" => 91, "url" => "https://example.test/bandeau.jpg"];
            }
            return ["ID" => 42, "url" => "https://example.test/chasse.jpg"];
        }');
        eval('function wp_get_attachment_image($id, $size = "thumbnail", $icon = false, $attr = []){
            $GLOBALS["cat_attachment_image"] = $id;
            return "<img class=\"hunt-banner__photo\" src=\"https://example.test/" . $id . ".jpg\">";
        }');

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('hunt-banner__frame', $html);
        $this->assertSame(91, $GLOBALS['cat_attachment_image']);
        $this->assertStringNotContainsString('data-hunt-sphere', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_still_banner_falls_back_to_the_hunt_image(): void
    {
        $this->bootShell();
        $GLOBALS['cat_attachment_image'] = null;

        eval('function esc_url($url){return $url;}');
        eval('function cat_get_primary_hunt_id(){return 7;}');
        eval('function get_the_title($id = 0){return "Antre de feu";}');
        eval('function get_field($key, $id){
            if ($key === "chasse_bandeau_accueil") {
                return null;
            }
            return ["ID" => 42, "url" => "https://example.test/chasse.jpg"];
        }');
        eval('function wp_get_attachment_image($id, $size = "thumbnail", $icon = false, $attr = []){
            $GLOBALS["cat_attachment_image"] = $id;
            return "<img class=\"hunt-banner__photo\" src=\"https://example.test/" . $id . ".jpg\">";
        }');

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('hunt-banner__frame', $html);
        $this->assertSame(42, $GLOBALS['cat_attachment_image']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_banner_video_stays_in_the_same_field_and_uses_the_hunt_image_as_poster(): void
    {
        $this->bootShell();

        eval('function esc_url($url){return $url;}');
        eval('function cat_get_primary_hunt_id(){return 7;}');
        eval('function get_the_title($id = 0){return "Antre de feu";}');
        eval('function get_field($key, $id){
            if ($key === "chasse_bandeau_accueil") {
                return [
                    "ID" => 91,
                    "url" => "https://example.test/bandeau.mp4",
                    "mime_type" => "video/mp4",
                ];
            }
            return [
                "ID" => 42,
                "url" => "https://example.test/chasse.jpg",
                "mime_type" => "image/jpeg",
            ];
        }');

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('class="hunt-banner__video"', $html);
        $this->assertStringContainsString('src="https://example.test/bandeau.mp4"', $html);
        $this->assertStringContainsString('autoplay', $html);
        $this->assertDoesNotMatchRegularExpression('/<video[^>]*\sloop[\s=>]/', $html);
        $this->assertStringContainsString('v.loop=false', $html);
        $this->assertStringContainsString('poster="https://example.test/chasse.jpg"', $html);
        $this->assertStringNotContainsString('prefers-reduced-motion', $html);
        $this->assertStringNotContainsString('data-src=', $html);
        $this->assertStringNotContainsString('hunt-banner__canvas', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_banner_video_plays_even_when_the_sphere_is_enabled(): void
    {
        $this->bootShell();

        eval('function esc_url($url){return $url;}');
        eval('function get_option($name, $default = false){
            return $GLOBALS["cat_options"][$name] ?? $default;
        }');
        $GLOBALS['cat_options'] = ['cta_hunt_banner_sphere_enabled' => '1'];
        eval('function cat_get_primary_hunt_id(){return 7;}');
        eval('function get_the_title($id = 0){return "Antre de feu";}');
        eval('function get_field($key, $id){
            if ($key === "chasse_bandeau_accueil") {
                return [
                    "ID" => 91,
                    "url" => "https://example.test/bandeau.mp4",
                    "mime_type" => "video/mp4",
                ];
            }
            return [
                "ID" => 42,
                "url" => "https://example.test/chasse.jpg",
                "mime_type" => "image/jpeg",
            ];
        }');

        ob_start();
        cta_shell_render_hunt_banner();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('src="https://example.test/bandeau.mp4"', $html);
        $this->assertStringContainsString('poster="https://example.test/chasse.jpg"', $html);
        $this->assertStringNotContainsString('data-hunt-sphere', $html);
        $this->assertStringNotContainsString('hunt-banner__canvas', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_home_banner_field_accepts_a_video_without_changing_its_definition(): void
    {
        $this->bootShell();

        $field = cta_shell_prepare_home_banner_field([
            'type' => 'image',
            'name' => 'chasse_bandeau_accueil',
            'return_format' => 'url',
        ]);
        $this->assertSame('file', $field['type']);
        $this->assertSame('array', $field['return_format']);
        $this->assertStringContainsString('mp4', $field['mime_types']);
        $this->assertSame('all', $field['library']);

        eval('function acf_is_screen($id = ""){return $id === "acf-field-group";}');
        $untouched = cta_shell_prepare_home_banner_field([
            'type' => 'image',
            'name' => 'chasse_bandeau_accueil',
            'return_format' => 'array',
        ]);
        $this->assertSame('image', $untouched['type']);

        $this->assertFalse(cta_shell_banner_field_allows_video(true, [
            'name' => 'chasse_bandeau_accueil',
        ]));
        $this->assertTrue(cta_shell_banner_field_allows_video(true, [
            'name' => 'chasse_principale_image',
        ]));

        eval('function get_post_mime_type($id){
            return (int) $id === 91 ? "video/mp4" : "image/jpeg";
        }');
        $this->assertTrue(cta_shell_banner_value_is_allowed('File must be a valid image.', 91));
        $this->assertSame(
            'File must be a valid image.',
            cta_shell_banner_value_is_allowed('File must be a valid image.', 42)
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_hunt_sphere_switch_is_an_admin_tools_tile(): void
    {
        $this->bootShell();
        eval('function esc_html_e($text, $domain = null){echo $text;}');
        eval('function esc_attr_e($text, $domain = null){echo $text;}');
        eval('function checked($checked){if ($checked){echo " checked=\"checked\"";}}');
        eval('function cta_shell_user_is_administrator(){return !empty($GLOBALS["cat_is_admin"]);}');
        eval('function get_option($name, $default = false){
            return $GLOBALS["cat_options"][$name] ?? $default;
        }');
        $GLOBALS['cat_options'] = [];
        $GLOBALS['cat_is_admin'] = false;

        $this->assertSame('', cta_shell_render_hunt_sphere_switch());

        $GLOBALS['cat_is_admin'] = true;
        $off = cta_shell_render_hunt_sphere_switch();
        $this->assertStringContainsString('id="hunt-banner-sphere-toggle"', $off);
        $this->assertStringContainsString('Sphère du bandeau', $off);
        $this->assertStringContainsString('Désactivée', $off);
        $this->assertStringNotContainsString('checked="checked"', $off);

        $GLOBALS['cat_options'] = ['cta_hunt_banner_sphere_enabled' => '1'];
        $on = cta_shell_render_hunt_sphere_switch();
        $this->assertStringContainsString('checked="checked"', $on);
        $this->assertStringContainsString('Activée', $on);

        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $outils = (string) file_get_contents($theme . '/template-parts/shell/outils.php');
        $this->assertStringContainsString(
            'if ($is_admin && function_exists(\'cta_shell_render_hunt_sphere_switch\'))',
            $outils
        );
        $script = (string) file_get_contents($theme . '/assets/js/hunt-banner-sphere-card.js');
        $this->assertStringContainsString('action=cta_toggle_hunt_banner_sphere', $script);
        $enqueue = (string) file_get_contents($theme . '/inc/admin-functions.php');
        $this->assertStringContainsString("cta_shell_request_slug() === 'outils'", $enqueue);
        $this->assertStringContainsString("current_user_can('administrator')", $enqueue);
        $this->assertStringContainsString("'hunt-banner-sphere-card'", $enqueue);
        $handler = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/plugins/chassesautresor-core/src/Admin/AdminAjaxHandler.php'
        );
        $this->assertStringContainsString("'cta_toggle_hunt_banner_sphere' => 'toggleHuntBannerSphere'", $handler);
        $this->assertStringContainsString("check_ajax_referer('cta_hunt_banner_sphere', 'nonce');", $handler);
        $scss = (string) file_get_contents($theme . '/assets/scss/_app-shell.scss');
        $this->assertStringContainsString('--hunt-banner-height: clamp(7.5rem, 18vh, 11rem);', $scss);
        $this->assertStringNotContainsString('--hunt-banner-height: 22rem;', $scss);
        $this->assertStringContainsString('.hunt-banner__frame picture img', $scss);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_virtual_pages_are_not_left_as_an_empty_singular_query(): void
    {
        $this->bootShell();

        $query = new stdClass();
        $query->is_404 = true;
        $query->is_single = true;
        $query->is_singular = true;
        $query->is_page = false;
        $query->queried_object = null;
        $query->queried_object_id = null;
        $query->post = null;
        $query->posts = [];
        $query->post_count = 0;
        $GLOBALS['post'] = null;

        cta_shell_normalize_virtual_query($query);

        $this->assertFalse($query->is_404);
        $this->assertFalse($query->is_single);
        $this->assertFalse($query->is_singular);
        $this->assertNull($query->queried_object);
        $this->assertNull($GLOBALS['post']);
    }

    public function test_home_pages_drop_the_welcome_and_tools_leave_the_home(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $layout = (string) file_get_contents($theme . '/templates/myaccount/layout.php');
        $organizer = (string) file_get_contents($theme . '/template-parts/myaccount/dashboard-organisateur.php');
        $admin = (string) file_get_contents($theme . '/template-parts/myaccount/dashboard-admin.php');
        $outils = (string) file_get_contents($theme . '/template-parts/shell/outils.php');

        $this->assertStringNotContainsString('Bienvenue', $layout);
        $this->assertStringNotContainsString('cat_render_cockpit_demo_notice', $layout);
        $this->assertStringNotContainsString(
            'présentée avant sa publication officielle',
            (string) file_get_contents($theme . '/inc/single-hunt-home.php')
        );
        $this->assertStringNotContainsString('Accès rapide édition', $organizer);
        $this->assertStringNotContainsString('Accès rapide édition', $admin);
        $this->assertStringNotContainsString('cat_render_hunt_quick_edit_card', $outils);
        $this->assertStringNotContainsString('Pilotage de la chasse', $outils);
        $this->assertStringNotContainsString('Éditez vos entités', $outils);
        $this->assertStringContainsString('myaccount-main', $outils);
        $this->assertStringContainsString('dashboard-grid', $outils);
        $this->assertStringContainsString('renderProtectionCard', $outils);
        $this->assertStringContainsString('Taux de conversion', $outils);
    }

    public function test_trophy_icon_is_shared_by_the_menu_and_the_top_bar(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $sidebar = (string) file_get_contents($theme . '/template-parts/shell/sidebar.php');
        $topbar = (string) file_get_contents($theme . '/template-parts/shell/topbar.php');

        $this->assertStringContainsString("cta_shell_icon(", $sidebar);
        $this->assertStringNotContainsString('organizer-nav', $sidebar);
        $this->assertStringContainsString("cta_shell_icon('trophy')", $topbar);
        $this->assertStringNotContainsString("cta_shell_icon('flame')", $topbar);
        $this->assertStringContainsString("cta_shell_icon('logout')", $topbar);
        $this->assertStringNotContainsString('Chasse au trésor', $topbar);
        $this->assertStringContainsString('Antre 2 feux', $topbar);
        $this->assertStringNotContainsString('app-topbar__hunt-mark', $topbar);
        $this->assertStringContainsString('solo', $topbar);
        $this->assertStringContainsString('cat_user_trophy_count', $topbar);
        $this->assertStringNotContainsString('app-topbar__trophy-count">0', $topbar);
        $this->assertStringNotContainsString('customer-logout', $topbar);
    }

    public function test_topbar_hunt_name_is_a_centered_gold_cinzel_title(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $scss = (string) file_get_contents($theme . '/assets/scss/_app-shell.scss');
        $functions = (string) file_get_contents($theme . '/functions.php');

        $this->assertMatchesRegularExpression(
            '/\.app-topbar__lead,\s*\.app-topbar__meta \{\s*flex:\s*1 1 0%;/s',
            $scss
        );
        $this->assertStringContainsString('font-family: "Cinzel", Palatino, "Palatino Linotype", serif;', $scss);
        $this->assertStringContainsString('text-transform: uppercase;', $scss);
        $this->assertStringContainsString('background-clip: text;', $scss);
        $this->assertStringNotContainsString('.app-topbar__hunt-mark', $scss);
        $this->assertStringContainsString('family=Cinzel:wght@600;700', $functions);
        $this->assertMatchesRegularExpression(
            '/html\[data-theme="day"\] \.app-topbar__hunt \{/',
            $scss
        );
    }

    public function test_profile_menu_script_no_longer_offers_logout(): void
    {
        $script = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/js/header-account-menu.js'
        );

        $this->assertStringNotContainsString('logoutUrl', $script);
        $this->assertStringNotContainsString('Déconnexion', $script);
    }

    public function test_day_theme_uses_the_light_shell_background_for_the_enigmas_canvas(): void
    {
        $scss = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_app-shell.scss'
        );

        $this->assertMatchesRegularExpression(
            '/html\[data-theme="day"\] \.home-enigmas-preserve \{\s*'
            . 'background-color:\s*var\(--shell-bg\);\s*'
            . 'background-image:\s*none;\s*'
            . 'color:\s*var\(--shell-text\);/s',
            $scss
        );
    }

    public function test_tools_cards_use_theme_aware_contrast_colors(): void
    {
        $scss = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/assets/scss/_app-shell.scss'
        );

        $this->assertMatchesRegularExpression(
            '/html\[data-theme="day"\]\s*\{[^}]*'
            . '--shell-state-active-text:\s*#146c3c;[^}]*'
            . '--shell-state-blocked-text:\s*#a42a20;/s',
            $scss
        );
        $this->assertStringContainsString(
            '.app-shell .hunt-lifecycle-card__label-on.is-action',
            $scss
        );
        $this->assertStringContainsString('color: var(--shell-muted);', $scss);
        $this->assertStringContainsString('background: var(--shell-primary-button-bg);', $scss);
        $this->assertStringContainsString('color: var(--shell-primary-button-text);', $scss);
        $this->assertStringContainsString('background: var(--shell-danger-button-bg);', $scss);
        $this->assertStringContainsString('color: var(--shell-danger-button-text);', $scss);
    }

    public function test_player_home_keeps_enigmas_and_moves_team_stats_off_home(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        $dashboard = (string) file_get_contents(
            $theme . '/template-parts/myaccount/dashboard-joueur.php'
        );
        $statsPage = (string) file_get_contents($theme . '/template-parts/shell/progression.php');
        $loop = (string) file_get_contents(
            $theme . '/template-parts/enigme/chasse-partial-boucle-enigmes.php'
        );

        $banner = strpos($dashboard, 'cta_shell_render_hunt_banner');
        $enigmas = strpos($dashboard, 'cat_render_single_hunt_home_enigmas');

        $this->assertNotFalse($banner);
        $this->assertNotFalse($enigmas);
        $this->assertLessThan($enigmas, $banner);
        $placeholder = (string) file_get_contents($theme . '/templates/shell-placeholder.php');
        $navigation = (string) file_get_contents($theme . '/inc/myaccount-functions.php');

        $this->assertStringNotContainsString('cta_shell_render_team_stats', $dashboard);
        $this->assertStringContainsString('cta_shell_render_team_stats', $statsPage);
        $this->assertStringContainsString("get_template_part('template-parts/shell/progression')", $placeholder);
        $this->assertStringNotContainsString("'statistiques'", $navigation);
        $this->assertStringContainsString('class="bloc-enigmes-chasse"', $loop);
        $this->assertStringNotContainsString('team-stat', $loop);
        $this->assertStringNotContainsString('app-shell', $loop);
    }

    public function test_staff_homes_keep_the_hunt_banner_before_the_enigmas(): void
    {
        $theme = dirname(__DIR__) . '/wp-content/themes/chassesautresor';
        foreach (['dashboard-organisateur.php', 'dashboard-admin.php'] as $file) {
            $dashboard = (string) file_get_contents($theme . '/template-parts/myaccount/' . $file);
            $banner = strpos($dashboard, 'cta_shell_render_hunt_banner');
            $enigmas = strpos($dashboard, 'cat_render_single_hunt_home_enigmas');

            $this->assertNotFalse($banner, $file);
            $this->assertNotFalse($enigmas, $file);
            $this->assertLessThan($enigmas, $banner, $file);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_home_sidebar_is_omitted_on_an_enigma_page(): void
    {
        if (!function_exists('is_singular')) {
            eval('function is_singular($postType = "") {
                $current = $GLOBALS["cat_test_singular"] ?? "";
                if ($postType === "") {
                    return $current !== "";
                }
                return $current === $postType;
            }');
        }
        $this->bootShell();

        $GLOBALS['cat_test_singular'] = 'enigme';
        $this->assertFalse(cta_shell_shows_sidebar());

        $GLOBALS['cat_test_singular'] = 'page';
        $this->assertTrue(cta_shell_shows_sidebar());

        $shell = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/app-shell.php'
        );
        $topbar = (string) file_get_contents(
            dirname(__DIR__) . '/wp-content/themes/chassesautresor/template-parts/shell/topbar.php'
        );
        $this->assertStringContainsString('app-shell--no-nav', $shell);
        $this->assertStringContainsString('cta_shell_shows_sidebar()', $topbar);
    }

    private function bootShell(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }
        if (!function_exists('__')) {
            eval('function __($text, $domain = null){return $text;}');
        }
        if (!function_exists('esc_html')) {
            eval('function esc_html($text){return $text;}');
        }
        if (!function_exists('esc_html__')) {
            eval('function esc_html__($text, $domain = null){return $text;}');
        }
        if (!function_exists('esc_attr')) {
            eval('function esc_attr($text){return $text;}');
        }
        if (!function_exists('esc_attr__')) {
            eval('function esc_attr__($text, $domain = null){return $text;}');
        }
        if (!function_exists('add_action')) {
            eval('function add_action($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }
        if (!function_exists('add_filter')) {
            eval('function add_filter($hook, $callback, $priority = 10, $accepted_args = 1){}');
        }

        require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/app-shell.php';
    }
}
