<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($cap)
    {
        return true;
    }
}

if (!function_exists('est_organisateur')) {
    function est_organisateur()
    {
        return true;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in()
    {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id()
    {
        return 1;
    }
}

if (!function_exists('get_organisateur_from_user')) {
    function get_organisateur_from_user($user_id)
    {
        return 99;
    }
}

if (!function_exists('recuperer_enigmes_tentatives_en_attente')) {
    function recuperer_enigmes_tentatives_en_attente($organisateur_id)
    {
        return [];
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key, $single)
    {
        if ($key === '_myaccount_flash_messages') {
            return $GLOBALS['test_myaccount_flash_meta'] ?? [];
        }

        if ($key === '_myaccount_messages') {
            return $GLOBALS['test_myaccount_persistent_meta'] ?? [];
        }

        return [
            [
                'statut' => 'en attente',
            ],
        ];
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $key, $value)
    {
        if ($key === '_myaccount_flash_messages') {
            $GLOBALS['test_myaccount_flash_meta'] = $value;
        }

        if ($key === '_myaccount_messages') {
            $GLOBALS['test_myaccount_persistent_meta'] = $value;
        }

        return true;
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $key)
    {
        if ($key === '_myaccount_flash_messages') {
            unset($GLOBALS['test_myaccount_flash_meta']);
        }

        if ($key === '_myaccount_messages') {
            unset($GLOBALS['test_myaccount_persistent_meta']);
        }

        return true;
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory()
    {
        return __DIR__;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data)
    {
        echo json_encode(['success' => true, 'data' => $data]);
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id)
    {
        if ($post_id === 99) {
            return 'https://example.com/organisateur';
        }

        if ($post_id === 101) {
            return 'https://example.com/chasse-101';
        }

        return 'https://example.com/post-' . $post_id;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post_id)
    {
        if ($post_id === 101) {
            return 'Chasse Example';
        }

        return 'Post ' . $post_id;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '')
    {
        $query = http_build_query($args);
        $sep = strpos($url, '?') === false ? '?' : '&';
        return $url . $sep . $query;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return $url;
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '')
    {
        return 'https://example.com' . $path;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!class_exists('PointsRepository')) {
    class PointsRepository
    {
        public function __construct($wpdb)
        {
        }

        public function getConversionRequests($userId, $status)
        {
            return [['id' => 1]];
        }
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args = [])
    {
        return [101];
    }
}

require_once __DIR__ . '/../inc/user-functions.php';

class MyAccountMessagesTest extends TestCase
{
    public function test_conversion_request_message_contains_link(): void
    {
        $output = myaccount_get_important_messages();

        $this->assertStringContainsString('<a', $output);
        $this->assertStringContainsString(
            'https://example.com/organisateur?edition=open&onglet=revenus',
            $output
        );
        $this->assertStringContainsString('demande de conversion', $output);
    }

    public function test_admin_conversion_request_link_points_to_points_tab(): void
    {
        $output = myaccount_get_important_messages();
        $this->assertStringContainsString(
            'https://example.com/mon-compte/?section=points',
            $output
        );
    }

    public function test_pending_validation_message_is_displayed(): void
    {
        $output = myaccount_get_important_messages();

        $this->assertStringContainsString('Demande pour', $output);
        $this->assertStringContainsString('en cours de traitement', $output);
        $this->assertStringContainsString(
            '<a href="https://example.com/chasse-101">Chasse Example</a>',
            $output
        );
    }

    public function test_pending_request_message_contains_riddle_link(): void
    {
        update_user_meta(
            1,
            '_myaccount_messages',
            [
                'tentative_123' => [
                    'text' => '<a href="https://example.com/enigme">Énigme</a>',
                    'type' => 'info',
                ],
            ]
        );

        $output = myaccount_get_important_messages();

        $this->assertStringContainsString('Votre demande de résolution de l\'énigme', $output);
        $this->assertStringContainsString('<a href="https://example.com/enigme">Énigme</a>', $output);

        delete_user_meta(1, '_myaccount_messages');
    }

    public function test_multiple_pending_requests_are_grouped(): void
    {
        update_user_meta(
            1,
            '_myaccount_messages',
            [
                'tentative_1' => [
                    'text' => '<a href="https://example.com/e1">E1</a>',
                    'type' => 'info',
                ],
                'tentative_2' => [
                    'text' => '<a href="https://example.com/e2">E2</a>',
                    'type' => 'info',
                ],
            ]
        );

        $output = myaccount_get_important_messages();

        $this->assertStringContainsString('Vos demandes de résolution d\'énigmes sont en cours de traitement', $output);
        $this->assertStringContainsString('<a class="etiquette" href="https://example.com/e1">E1</a>', $output);
        $this->assertStringContainsString('<a class="etiquette" href="https://example.com/e2">E2</a>', $output);

        delete_user_meta(1, '_myaccount_messages');
    }

    public function test_pending_request_with_full_message_is_not_duplicated(): void
    {
        $stored = "Votre demande de résolution de l'énigme <a href=\"https://example.com/enigme\">Énigme</a> est en cours de traitement. Vous recevrez une notification dès que votre demande sera traitée.";
        update_user_meta(
            1,
            '_myaccount_messages',
            [
                'tentative_456' => [
                    'text' => $stored,
                    'type' => 'info',
                ],
            ]
        );

        $output = myaccount_get_important_messages();

        $this->assertSame(1, substr_count($output, "Votre demande de résolution de l'énigme"));
        $this->assertStringContainsString('<a href="https://example.com/enigme">Énigme</a>', $output);

        delete_user_meta(1, '_myaccount_messages');
    }

    public function test_flash_message_is_displayed_once(): void
    {
        update_user_meta(
            1,
            '_myaccount_flash_messages',
            [
                ['text' => 'Message unique', 'type' => 'info'],
            ]
        );

        $first = myaccount_get_important_messages();
        $this->assertStringContainsString('Message unique', $first);

        $second = myaccount_get_important_messages();
        $this->assertStringNotContainsString('Message unique', $second);
    }

    public function test_persistent_message_persists_until_removed(): void
    {
        update_user_meta(
            1,
            '_myaccount_messages',
            [
                'foo' => ['text' => 'Persiste', 'type' => 'info'],
            ]
        );

        $first = myaccount_get_important_messages();
        $this->assertStringContainsString('Persiste', $first);

        $second = myaccount_get_important_messages();
        $this->assertStringContainsString('Persiste', $second);

        myaccount_remove_persistent_message(1, 'foo');
        $third = myaccount_get_important_messages();
        $this->assertStringNotContainsString('Persiste', $third);
    }

    public function test_messages_are_styled(): void
    {
        update_user_meta(
            1,
            '_myaccount_flash_messages',
            [
                ['text' => 'Stylé', 'type' => 'info'],
            ]
        );
        $output = myaccount_get_important_messages();
        $this->assertStringContainsString('<p class="message-info" role="status" aria-live="polite">Stylé</p>', $output);
    }

    public function test_dismissible_message_has_button(): void
    {
        update_user_meta(
            1,
            '_myaccount_messages',
            [
                'foo' => [
                    'text'        => 'Salut',
                    'type'        => 'info',
                    'dismissible' => true,
                ],
            ]
        );

        $output = myaccount_get_important_messages();
        $this->assertStringContainsString('class="message-close" data-key="foo"', $output);

        delete_user_meta(1, '_myaccount_messages');
    }

    public function test_ajax_section_returns_flash_message(): void
    {
        update_user_meta(
            1,
            '_myaccount_flash_messages',
            [
                ['text' => 'Via AJAX', 'type' => 'info'],
            ]
        );
        $_GET['section'] = 'organisateurs';

        ob_start();
        ca_load_admin_section();
        $json = ob_get_clean();
        $data = json_decode($json, true);

        $this->assertTrue($data['success']);
        $this->assertStringContainsString('Via AJAX', $data['data']['messages']);
        $this->assertSame([], get_user_meta(1, '_myaccount_flash_messages', true));

        unset($_GET['section']);
    }
}
