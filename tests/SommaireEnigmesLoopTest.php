<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SommaireEnigmesLoopTest extends TestCase
{
    public function testPlayerFilterKeepsOnlyTheAccessibleState(): void
    {
        require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/single-hunt-home.php';

        self::assertTrue(cat_sommaire_enigme_est_visible('accessible', false));
        self::assertFalse(cat_sommaire_enigme_est_visible('bloquee_pre_requis', false));
        self::assertFalse(cat_sommaire_enigme_est_visible('bloquee_date', false));
        self::assertFalse(cat_sommaire_enigme_est_visible('bloquee_chasse', false));
        self::assertFalse(cat_sommaire_enigme_est_visible('invalide', false));
        self::assertTrue(cat_sommaire_enigme_est_visible('bloquee_pre_requis', true));
        self::assertTrue(cat_sommaire_enigme_est_visible('accessible', true));
    }

    public function testHuntPageReturnsHomeUnlessAnAuthorizedEditorOpensIt(): void
    {
        require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/single-hunt-home.php';

        self::assertTrue(cat_single_hunt_request_returns_home('', false));
        self::assertTrue(cat_single_hunt_request_returns_home('', true));
        self::assertTrue(cat_single_hunt_request_returns_home('open', false));
        self::assertFalse(cat_single_hunt_request_returns_home('open', true));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPlayerSommaireListsAccessibleEnigmasWithAlbumNumbers(): void
    {
        $html = $this->renderSommaire(false);

        self::assertStringContainsString('class="sommaire-bd"', $html);
        self::assertStringContainsString('La carte muette', $html);
        self::assertStringContainsString('La dernière case', $html);
        self::assertStringNotContainsString('Le coffre scellé', $html);
        self::assertStringContainsString('sommaire-bd__pointilles', $html);
        self::assertStringContainsString('sommaire-bd__numero">1<', $html);
        self::assertStringNotContainsString('sommaire-bd__numero">2<', $html);
        self::assertStringContainsString('sommaire-bd__numero">3<', $html);
        self::assertStringContainsString('data-image="501"', $html);
        self::assertStringContainsString('sommaire-bd__case', $html);
        self::assertStringContainsString('Énigme trouvée.', $html);
        self::assertMatchesRegularExpression(
            '/<a class="sommaire-bd__lien" href="\/enigme\/11">.*La carte muette.*sommaire-bd__numero">1</s',
            $html
        );
        self::assertMatchesRegularExpression(
            '/<a class="sommaire-bd__lien" href="\/enigme\/13">.*La dernière case.*sommaire-bd__numero">3</s',
            $html
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPrivilegedSommaireKeepsLockedChapters(): void
    {
        $html = $this->renderSommaire(true);

        self::assertStringContainsString('Le coffre scellé', $html);
        self::assertStringContainsString('sommaire-bd__numero">2<', $html);
        self::assertStringContainsString('href="/enigme/12"', $html);
        self::assertStringContainsString('sommaire-bd__item--verrouillee', $html);
        self::assertStringContainsString('Nécessite', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAuthorizedRolesGetAddEditReorderAndSettings(): void
    {
        $html = $this->renderSommaire(true, true, [
            'add' => true,
            'edit_hunt' => true,
            'edit_riddle' => true,
            'validation' => 'creation',
        ]);

        self::assertStringContainsString('Ajouter une énigme', $html);
        self::assertStringContainsString('fa-plus', $html);
        self::assertStringContainsString('Éditer la chasse', $html);
        self::assertStringContainsString('https://example.test/outils/?chasse=1&section=parametres', $html);
        self::assertStringContainsString('https://example.test/outils/?enigme=11&section=parametres', $html);
        self::assertStringNotContainsString('edition=open', $html);
        self::assertStringContainsString('data-reorder="1"', $html);
        self::assertStringContainsString('sommaire-bd__poignee', $html);
        self::assertStringContainsString('sommaire-bd__reglages', $html);
        self::assertStringContainsString('fa-coins', $html);
        self::assertStringContainsString('sommaire-bd__trouvee', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddStaysVisibleButInactiveWhileAnEnigmaIsIncomplete(): void
    {
        $html = $this->renderSommaire(true, true, [
            'add' => true,
            'incomplete' => true,
        ]);

        self::assertStringContainsString('disabled', $html);
        self::assertStringContainsString('Terminez l’énigme incomplète', $html);
        self::assertStringNotContainsString('href="https://example.test/creer-enigme/', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testVisitorOutsideTheHuntSeesNoSommaire(): void
    {
        $html = $this->renderSommaire(false, false);

        self::assertSame('', $html);
    }

    /**
     * @param array{add?:bool,edit_hunt?:bool,edit_riddle?:bool,incomplete?:bool,validation?:string} $flags
     */
    private function renderSommaire(bool $privileged, bool $engaged = true, array $flags = []): string
    {
        $this->bootStubs();
        $GLOBALS['sommaire_privileged'] = $privileged;
        $GLOBALS['sommaire_engaged'] = $engaged;
        $GLOBALS['sommaire_can_add'] = $flags['add'] ?? false;
        $GLOBALS['sommaire_can_edit_hunt'] = $flags['edit_hunt'] ?? false;
        $GLOBALS['sommaire_can_edit_riddle'] = $flags['edit_riddle'] ?? false;
        $GLOBALS['sommaire_incomplete'] = $flags['incomplete'] ?? false;

        require_once dirname(__DIR__) . '/wp-content/themes/chassesautresor/inc/single-hunt-home.php';

        $args = [
            'chasse_id' => 1,
            'est_orga_associe' => $privileged,
            'infos_chasse' => [
                'statut' => 'en_cours',
                'statut_validation' => $flags['validation'] ?? 'valide',
                'enigmes_associees' => [11, 12, 13],
            ],
            'show_help_icon' => false,
        ];

        ob_start();
        require dirname(__DIR__)
            . '/wp-content/themes/chassesautresor/template-parts/enigme/chasse-partial-sommaire-enigmes.php';
        return (string) ob_get_clean();
    }

    private function bootStubs(): void
    {
        if (!function_exists('get_post_type')) {
            eval('function get_post_type($id) { return $id === 1 ? "chasse" : "enigme"; }');
        }
        if (!function_exists('get_current_user_id')) {
            eval('function get_current_user_id() { return 7; }');
        }
        if (!function_exists('chasse_est_visible_pour_utilisateur')) {
            eval('function chasse_est_visible_pour_utilisateur($chasse_id, $user_id) { return true; }');
        }
        if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
            eval('function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id) {'
                . ' return !empty($GLOBALS["sommaire_privileged"]); }');
        }
        if (!function_exists('user_can')) {
            eval('function user_can($user_id, $capability) {'
                . ' return !empty($GLOBALS["sommaire_privileged"]); }');
        }
        if (!function_exists('utilisateur_est_engage_dans_chasse')) {
            eval('function utilisateur_est_engage_dans_chasse($user_id, $chasse_id) {'
                . ' return !empty($GLOBALS["sommaire_engaged"]); }');
        }
        if (!function_exists('get_posts')) {
            eval('function get_posts($args) {'
                . ' $posts = [];'
                . ' foreach ([11, 12, 13] as $id) {'
                . '   $post = new stdClass();'
                . '   $post->ID = $id;'
                . '   $posts[] = $post;'
                . ' }'
                . ' return $posts; }');
        }
        if (!function_exists('get_cta_enigme')) {
            eval('function get_cta_enigme($enigme_id, $user_id = null) {'
                . ' $rows = ['
                . '   11 => ["etat_systeme" => "accessible", "action" => "link",'
                . '     "url" => "/enigme/11", "statut_utilisateur" => "en_cours"],'
                . '   12 => ["etat_systeme" => "bloquee_pre_requis", "action" => "link",'
                . '     "url" => "/enigme/12", "statut_utilisateur" => "non_commencee"],'
                . '   13 => ["etat_systeme" => "accessible", "action" => "link",'
                . '     "url" => "/enigme/13", "statut_utilisateur" => "resolue"],'
                . ' ];'
                . ' return $rows[(int) $enigme_id] ?? ["etat_systeme" => "invalide", "action" => "disabled"];'
                . ' }');
        }
        if (!function_exists('get_the_title')) {
            eval('function get_the_title($id) {'
                . ' $titles = [11 => "La carte muette", 12 => "Le coffre scellé", 13 => "La dernière case"];'
                . ' return $titles[(int) $id] ?? ""; }');
        }
        if (!function_exists('get_permalink')) {
            eval('function get_permalink($id) { return "/enigme/" . (int) $id; }');
        }
        if (!function_exists('get_field')) {
            eval('function get_field($key, $post_id = null, $format = true) {'
                . ' if ($key === "enigme_visuel_image" && (int) $post_id === 11) { return [501]; }'
                . ' if ($key === "enigme_cache_complet") { return empty($GLOBALS["sommaire_incomplete"]); }'
                . ' if ($key === "enigme_mode_validation" && (int) $post_id === 11) { return "automatique"; }'
                . ' if ($key === "enigme_tentative_cout_points" && (int) $post_id === 11) { return 3; }'
                . ' if ($key === "enigme_acces_pre_requis" && (int) $post_id === 12) { return [11]; }'
                . ' return null; }');
        }
        if (!function_exists('esc_attr_e')) {
            eval('function esc_attr_e($text, $domain = null) {'
                . ' echo htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8"); }');
        }
        if (!function_exists('add_query_arg')) {
            eval('function add_query_arg($args, $url) {'
                . ' return $url . "?" . http_build_query($args); }');
        }
        if (!function_exists('home_url')) {
            eval('function home_url($path = "/") { return "https://example.test" . $path; }');
        }
        if (!function_exists('wp_create_nonce')) {
            eval('function wp_create_nonce($action) { return "nonce"; }');
        }
        if (!function_exists('utilisateur_peut_ajouter_enigme')) {
            eval('function utilisateur_peut_ajouter_enigme($chasse_id, $user_id = null) {'
                . ' return !empty($GLOBALS["sommaire_can_add"]); }');
        }
        if (!function_exists('utilisateur_peut_modifier_post')) {
            eval('function utilisateur_peut_modifier_post($id) {'
                . ' return !empty($GLOBALS["sommaire_can_edit_hunt"]); }');
        }
        if (!function_exists('verifier_ou_mettre_a_jour_cache_complet')) {
            eval('function verifier_ou_mettre_a_jour_cache_complet($id) {}');
        }
        if (!function_exists('cta_shell_normalize_edition_section')) {
            eval('function cta_shell_normalize_edition_section($type, $section) {'
                . ' $map = ["param" => "parametres", "soumission" => "tentatives"];'
                . ' return $map[$section] ?? $section; }');
        }
        if (!function_exists('cta_shell_edition_url')) {
            eval('function cta_shell_edition_url($type, $id, $section = "parametres") {'
                . ' return "https://example.test/outils/?" . $type . "=" . (int) $id . "&section=" . $section; }');
        }
        if (!function_exists('utilisateur_peut_modifier_enigme')) {
            eval('function utilisateur_peut_modifier_enigme($id) {'
                . ' return !empty($GLOBALS["sommaire_can_edit_riddle"]); }');
        }
        if (!function_exists('build_picture_enigme')) {
            eval('function build_picture_enigme($image_id, $alt, $sizes, $attrs = []) {'
                . ' return "<picture data-image=\"" . (int) $image_id . "\"></picture>"; }');
        }
        if (!function_exists('esc_html')) {
            eval('function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8"); }');
        }
        if (!function_exists('esc_html__')) {
            eval('function esc_html__($text, $domain = null) { return $text; }');
        }
        if (!function_exists('esc_html_e')) {
            eval('function esc_html_e($text, $domain = null) { echo $text; }');
        }
        if (!function_exists('esc_attr')) {
            eval('function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8"); }');
        }
        if (!function_exists('esc_url')) {
            eval('function esc_url($url) { return (string) $url; }');
        }
        if (!function_exists('__')) {
            eval('function __($text, $domain = null) { return $text; }');
        }
    }
}
