<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        global $test_is_logged_in;
        return (bool) $test_is_logged_in;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        global $test_current_user_id;
        return (int) $test_current_user_id;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post_id)
    {
        global $test_post_types;
        return $test_post_types[$post_id] ?? null;
    }
}

if (!function_exists('get_post_status')) {
    function get_post_status($post_id)
    {
        global $test_post_statuses;
        return $test_post_statuses[$post_id] ?? '';
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = false)
    {
        global $test_post_meta;
        if (isset($test_post_meta[$post_id][$key])) {
            return $test_post_meta[$post_id][$key];
        }
        return '';
    }
}

if (!function_exists('utilisateur_est_organisateur_associe_a_chasse')) {
    function utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
    {
        global $test_associations;
        return !empty($test_associations[$user_id][$chasse_id]);
    }
}

if (!function_exists('recuperer_ids_enigmes_pour_chasse')) {
    function recuperer_ids_enigmes_pour_chasse($chasse_id): array
    {
        global $test_enigmes;
        return $test_enigmes[$chasse_id] ?? [];
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

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null): void
    {
        if (is_array($data)) {
            throw new Exception(json_encode($data));
        }
        throw new Exception((string) $data);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null)
    {
        global $json_success;
        $json_success = $data;
        return $data;
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

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/edition/edition-chasse.php';

final class SupprimerChasseAjaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $test_is_logged_in, $test_post_types, $test_post_statuses, $test_post_meta;
        global $test_associations, $test_current_user_id, $test_enigmes, $trashed_posts;
        global $wp_trash_return_values, $deleted_folders, $synced_chasses, $json_success;
        global $flash_messages, $test_attachments;

        $_POST = [];

        $test_is_logged_in = true;
        $test_post_types = [123 => 'chasse'];
        $test_post_statuses = [123 => 'pending'];
        $test_post_meta = [123 => ['chasse_cache_statut' => 'revision']];
        $test_associations = [1 => [123 => true]];
        $test_current_user_id = 1;
        $test_enigmes = [123 => [10, 11]];
        $trashed_posts = [];
        $wp_trash_return_values = [];
        $deleted_folders = [];
        $synced_chasses = [];
        $json_success = null;
        $flash_messages = [];
        $test_attachments = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_refuse_when_user_not_logged_in(): void
    {
        global $test_is_logged_in;
        $test_is_logged_in = false;
        $_POST['chasse_id'] = 123;

        try {
            supprimer_chasse_ajax();
            $this->fail('Expected exception not thrown');
        } catch (Exception $exception) {
            $this->assertSame('non_connecte', $exception->getMessage());
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_refuse_when_chasse_not_in_revision(): void
    {
        global $test_post_meta;
        $test_post_meta[123]['chasse_cache_statut'] = 'publication';
        $_POST['chasse_id'] = 123;

        $this->expectExceptionMessage('chasse_ineligible');
        supprimer_chasse_ajax();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_refuse_when_chasse_status_not_pending(): void
    {
        global $test_post_statuses;
        $test_post_statuses[123] = 'draft';
        $_POST['chasse_id'] = 123;

        $this->expectExceptionMessage('chasse_ineligible');
        supprimer_chasse_ajax();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_deletes_chasse_and_enigmes(): void
    {
        global $trashed_posts, $deleted_folders, $synced_chasses, $json_success, $flash_messages;
        $_POST['chasse_id'] = 123;

        supprimer_chasse_ajax();

        $this->assertSame([10, 11, 123], $trashed_posts);
        $this->assertSame([10, 11], $deleted_folders);
        $this->assertSame([[123, true, true]], $synced_chasses);
        $this->assertSame(
            ['redirect' => 'https://example.com/mon-compte/organisateurs/'],
            $json_success
        );
        $this->assertCount(1, $flash_messages);
        $this->assertSame(
            [
                'user_id'     => 1,
                'message'     => 'Votre chasse a été supprimée. Vous pouvez en créer une nouvelle quand vous le souhaitez.',
                'type'        => 'success',
                'dismissible' => true,
            ],
            $flash_messages[0]
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_trashes_attachments(): void
    {
        global $trashed_posts, $test_attachments;

        $_POST['chasse_id'] = 123;
        $test_attachments = [
            123 => [
                (object) ['ID' => 90],
                (object) ['ID' => 91],
            ],
        ];

        supprimer_chasse_ajax();

        $this->assertSame([10, 11, 90, 91, 123], $trashed_posts);
    }
}
