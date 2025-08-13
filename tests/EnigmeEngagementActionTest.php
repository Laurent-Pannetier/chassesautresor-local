<?php
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

$last_action = null;
function do_action($tag, ...$args): void
{
    global $last_action;
    $last_action = ['tag' => $tag, 'args' => $args];
}

function enigme_mettre_a_jour_statut_utilisateur(int $enigme_id, int $user_id, string $statut, bool $forcer = false): bool
{
    return true;
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/enigme/engagements.php';

class EnigmeEngagementActionTest extends TestCase
{
    public function test_marquer_enigme_comme_engagee_triggers_action(): void
    {
        global $wpdb, $last_action;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public function get_var($query)
            {
                return 0;
            }
            public function insert($table, $data, $format)
            {
                return 1;
            }
            public function prepare($query, ...$args)
            {
                $params = $args[0] ?? [];
                if (is_array($params) && count($args) === 1) {
                    $args = $params;
                }
                if (!$args) {
                    return $query;
                }
                return vsprintf($query, $args);
            }
        };

        $this->assertTrue(marquer_enigme_comme_engagee(5, 10));
        $this->assertNotNull($last_action);
        $this->assertSame('enigme_engagee', $last_action['tag']);
        $this->assertSame([5, 10], $last_action['args']);
    }
}
