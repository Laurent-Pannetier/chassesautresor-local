<?php
use PHPUnit\Framework\TestCase;

if (!function_exists('enigme_mettre_a_jour_statut_utilisateur')) {
    function enigme_mettre_a_jour_statut_utilisateur($eid, $uid, $statut, $force = false) { return true; }
}
if (!function_exists('current_time')) {
    function current_time($type) { return '2024-01-01 00:00:00'; }
}
if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {}
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/engagements.php';

class EnigmeEngagementTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_ensure_enigme_engagement_registers_when_missing(): void
    {
        global $wpdb;
        $wpdb = new WPDBStubInsert();
        $res = ensure_enigme_engagement(1, 2);
        $this->assertTrue($res);
        $this->assertCount(1, $wpdb->inserted);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_ensure_enigme_engagement_skips_when_existing(): void
    {
        global $wpdb;
        $wpdb = new WPDBStubNoInsert();
        $res = ensure_enigme_engagement(1, 2);
        $this->assertFalse($res);
        $this->assertFalse($wpdb->inserted);
    }
}

class WPDBStubInsert
{
    public string $prefix = 'wp_';
    public array $inserted = [];
    public function get_var($query) { return 0; }
    public function prepare($query, ...$args) { return $query; }
    public function insert($table, $data, $formats) { $this->inserted[] = $data; return true; }
}

class WPDBStubNoInsert
{
    public string $prefix = 'wp_';
    public bool $inserted = false;
    public function get_var($query) { return 1; }
    public function prepare($query, ...$args) { return $query; }
    public function insert($table, $data, $formats) { $this->inserted = true; return true; }
}
