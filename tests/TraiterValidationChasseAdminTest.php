<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('RedirectException')) {
    class RedirectException extends Exception
    {
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability): bool
    {
        global $test_current_user_can;
        return $test_current_user_can[$capability] ?? false;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message)
    {
        throw new RuntimeException(is_string($message) ? $message : 'wp_die');
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post_id)
    {
        global $test_post_types;
        return $test_post_types[$post_id] ?? null;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action)
    {
        global $test_nonces;
        return !empty($test_nonces[$action]) && $test_nonces[$action] === $nonce;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text)
    {
        return is_string($text) ? $text : '';
    }
}

if (!function_exists('recuperer_enigmes_associees')) {
    function recuperer_enigmes_associees($chasse_id): array
    {
        global $test_enigmes_associees;
        return $test_enigmes_associees[$chasse_id] ?? [];
    }
}

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse($chasse_id): array
    {
        global $test_enigmes;
        return $test_enigmes[$chasse_id] ?? [];
    }
}

if (!function_exists('get_organisateur_from_chasse')) {
    function get_organisateur_from_chasse($chasse_id)
    {
        global $test_organisateurs;
        return $test_organisateurs[$chasse_id] ?? 0;
    }
}

if (!function_exists('get_field')) {
    function get_field($key, $post_id)
    {
        global $test_fields;
        return $test_fields[$post_id][$key] ?? [];
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post_id)
    {
        global $test_titles;
        return $test_titles[$post_id] ?? '';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id)
    {
        global $test_permalinks;
        return $test_permalinks[$post_id] ?? '';
    }
}

if (!function_exists('wp_trash_post')) {
    function wp_trash_post($post_id)
    {
        global $trashed_posts, $wp_trash_return_values;
        $trashed_posts[] = $post_id;
        if (isset($wp_trash_return_values[$post_id])) {
            return $wp_trash_return_values[$post_id];
        }
        return true;
    }
}

if (!function_exists('supprimer_dossier_enigme')) {
    function supprimer_dossier_enigme($post_id): void
    {
        global $deleted_folders;
        $deleted_folders[] = $post_id;
    }
}

if (!function_exists('synchroniser_cache_enigmes_chasse')) {
    function synchroniser_cache_enigmes_chasse($chasse_id, $forcer = false, $nettoyer = false): void
    {
        global $synced_chasses;
        $synced_chasses[] = [$chasse_id, $forcer, $nettoyer];
    }
}

if (!function_exists('get_attached_media')) {
    function get_attached_media($type, $post_id)
    {
        global $test_attachments;
        return $test_attachments[$post_id] ?? [];
    }
}

if (!function_exists('home_url')) {
    function home_url($path = ''): string
    {
        return 'https://example.com' . $path;
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($url): void
    {
        global $redirect_url;
        $redirect_url = $url;
        throw new RedirectException('redirect');
    }
}

if (!function_exists('myaccount_add_flash_message')) {
    function myaccount_add_flash_message(int $user_id, string $message, string $type = 'info', bool $dismissible = false): void
    {
        global $flash_messages;
        $flash_messages[] = compact('user_id', 'message', 'type', 'dismissible');
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!function_exists('get_option')) {
    function get_option($name)
    {
        global $test_options;
        return $test_options[$name] ?? '';
    }
}

if (!function_exists('is_email')) {
    function is_email($email)
    {
        return is_string($email) && strpos($email, '@') !== false;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1): void
    {
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook_name, $callback, $priority = 10): void
    {
    }
}

if (!function_exists('cta_send_email')) {
    function cta_send_email($email, $subject, $body, array $headers = []): void
    {
        global $sent_emails;
        $sent_emails[] = compact('email', 'subject', 'body', 'headers');
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-chasse.php';
require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/admin-functions.php';

final class TraiterValidationChasseAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $test_current_user_can, $test_post_types, $test_nonces, $test_enigmes_associees, $test_enigmes;
        global $test_organisateurs, $test_fields, $test_titles, $test_permalinks, $trashed_posts;
        global $wp_trash_return_values, $deleted_folders, $synced_chasses, $test_attachments, $redirect_url;
        global $flash_messages, $sent_emails, $test_options;

        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $test_current_user_can = ['administrator' => true];
        $test_post_types = [123 => 'chasse'];
        $test_nonces = [];
        $test_enigmes_associees = [123 => [10, 11]];
        $test_enigmes = [123 => [10, 11]];
        $test_organisateurs = [123 => 501];
        $test_fields = [
            501 => [
                'utilisateurs_associes' => [
                    (object) ['ID' => 99],
                ],
                'email_organisateur' => 'orga@example.com',
            ],
        ];
        $test_titles = [123 => 'Chasse Test'];
        $test_permalinks = [123 => 'https://example.com/chasse/123'];
        $trashed_posts = [];
        $wp_trash_return_values = [];
        $deleted_folders = [];
        $synced_chasses = [];
        $test_attachments = [
            123 => [
                (object) ['ID' => 90],
            ],
        ];
        $redirect_url = null;
        $flash_messages = [];
        $sent_emails = [];
        $test_options = [
            'admin_email' => 'admin@example.com',
        ];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_supprimer_action_trashes_chasse_and_children(): void
    {
        global $test_nonces, $trashed_posts, $deleted_folders, $synced_chasses, $redirect_url;
        global $flash_messages, $sent_emails;

        $_POST['validation_admin_action'] = 'supprimer';
        $_POST['chasse_id'] = 123;
        $_POST['validation_admin_nonce'] = 'nonce-ok';
        $test_nonces['validation_admin_123'] = 'nonce-ok';

        try {
            traiter_validation_chasse_admin();
            $this->fail('Expected redirect not triggered');
        } catch (RedirectException $exception) {
            $this->assertSame('redirect', $exception->getMessage());
        }

        $this->assertSame([10, 11, 90, 123], $trashed_posts);
        $this->assertSame([10, 11], $deleted_folders);
        $this->assertSame([[123, true, true]], $synced_chasses);
        $this->assertSame('https://example.com/mon-compte/organisateurs/', $redirect_url);
        $this->assertCount(1, $sent_emails);
        $this->assertSame([
            [
                'user_id' => 99,
                'message' => 'Votre chasse « Chasse Test » a été supprimée.',
                'type' => 'error',
                'dismissible' => false,
            ],
        ], $flash_messages);
    }
}
