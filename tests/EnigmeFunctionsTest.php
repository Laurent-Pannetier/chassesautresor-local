<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../wp-content/themes/chassesautresor/inc/enigme/tentatives.php';

class EnigmeFunctionsTest extends TestCase {
    public function test_compter_tentatives_du_jour() {
        global $wpdb;
        $wpdb = new class {
            public array $params = [];
            public function prepare($q, ...$args){ $this->params = $args; return $q; }
            public function get_var($query){ return 2; }
            public string $prefix = 'wp_';
        };
        $res = compter_tentatives_du_jour(1, 5);
        $this->assertSame(2, $res);
        $this->assertSame([1,5], [$wpdb->params[0], $wpdb->params[1]]);
    }
}
