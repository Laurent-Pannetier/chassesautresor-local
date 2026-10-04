<?php

use PHPUnit\Framework\TestCase;

class MyAccountOrganizerNavTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_enigmes_are_linked_when_chasse_pending(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        eval('function get_organisateur_from_user($user_id){return 99;}');
        eval(
            'function get_post_status($post_id){' .
            '$s=[99=>"publish",100=>"pending",200=>"pending"];' .
            'return $s[$post_id]??"publish";}'
        );
        eval(
            'function get_field($key,$id){' .
            '$f=[' .
            '99=>["organisateur_cache_complet"=>true],' .
            '100=>["chasse_cache_statut_validation"=>"en_attente","chasse_cache_complet"=>true],' .
            '200=>["enigme_cache_complet"=>true,"enigme_cache_etat_systeme"=>"accessible"]' .
            '];' .
            'return $f[$id][$key]??null;}'
        );
        eval('function get_posts($args=[]){return [(object)["ID"=>100]];}');
        eval('function recuperer_enigmes_tentatives_en_attente($org){return [];}');
        eval('function recuperer_ids_enigmes_pour_chasse($cid){return [200];}');
        eval('function get_permalink($id){return "https://example.com/post-$id";}');
        eval('function get_the_title($id){return "Post $id";}');
        eval('function peut_valider_chasse($cid,$uid){return false;}');
        eval('function cat_is_platform_mode(){return false;}');
        eval('function esc_url($url){return $url;}');
        eval('function esc_attr($value){return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");}');
        eval('function esc_html($value){return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");}');
        eval('function esc_attr_e($value){echo htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");}');

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/myaccount-functions.php';

        $nav = myaccount_get_organizer_nav(1);

        $this->assertNotNull($nav);
        $this->assertFalse($nav['show_organizer']);
        $this->assertSame('https://example.com/post-200', $nav['chasses'][0]['enigmes'][0]['url']);

        $html = myaccount_render_organizer_nav($nav);
        $this->assertStringNotContainsString('fa-landmark', $html);
        $this->assertStringNotContainsString('Post 99', $html);
        $this->assertStringContainsString('organizer-nav--hunt-first', $html);
        $this->assertStringContainsString('organizer-nav-hunt', $html);
        $this->assertStringContainsString('organizer-nav-riddle', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_chasse_eligible_has_special_status(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        eval('function get_organisateur_from_user($user_id){return 99;}');
        eval('function get_post_status($post_id){$s=[99=>"publish",100=>"pending"];return $s[$post_id]??"publish";}');
        eval('function get_field($key,$id){$f=[99=>["organisateur_cache_complet"=>true],100=>["chasse_cache_statut_validation"=>"creation","chasse_cache_complet"=>true]];return $f[$id][$key]??null;}');
        eval('function get_posts($args=[]){return [(object)["ID"=>100]];}');
        eval('function recuperer_enigmes_tentatives_en_attente($org){return [];}');
        eval('function recuperer_ids_enigmes_pour_chasse($cid){return []; }');
        eval('function get_permalink($id){return "https://example.com/post-$id";}');
        eval('function get_the_title($id){return "Post $id";}');
        eval('function peut_valider_chasse($cid,$uid){return true;}');
        eval('function cat_is_platform_mode(){return true;}');
        eval('function esc_url($url){return $url;}');
        eval('function esc_attr($value){return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");}');
        eval('function esc_html($value){return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");}');
        eval('function esc_attr_e($value){echo htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");}');

        require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/myaccount-functions.php';

        $nav = myaccount_get_organizer_nav(1);

        $this->assertNotNull($nav);
        $this->assertTrue($nav['show_organizer']);
        $this->assertStringContainsString('status-eligible', $nav['chasses'][0]['classes']);

        $html = myaccount_render_organizer_nav($nav);
        $this->assertStringContainsString('fa-landmark', $html);
        $this->assertStringContainsString('Post 99', $html);
    }
}

