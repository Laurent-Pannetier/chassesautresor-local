<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Create the service responsible for persistent site messages.
 */
function cat_get_site_message_service(): ChassesAuTresor\Core\Messages\SiteMessageService {
    global $wpdb;
    return ChassesAuTresor\Core\Support\CoreServiceFactory::siteMessages($wpdb);
}

/**
 * Store a site-wide message.
 *
 * @param string      $type        Message type used as CSS class.
 * @param string      $content     Message content.
 * @param bool        $persistent  Whether the message should persist across sessions.
 * @param string|null $message_key Optional translation key.
 * @param string|null $locale      Optional locale for the message.
 * @param int|null    $expires     Expiration as timestamp or duration in seconds.
 * @param bool        $dismissible Whether the message can be dismissed.
 *
 * @return void
 */
function add_site_message(
    string $type,
    string $content,
    bool $persistent = false,
    ?string $message_key = null,
    ?string $locale = null,
    ?int $expires = null,
    bool $dismissible = false
): void {
    $message = [
        'type'        => $type,
        'content'     => $content,
        'dismissible' => $dismissible,
    ];

    if ($message_key !== null) {
        $message['message_key'] = $message_key;
    }

    if ($locale !== null) {
        $message['locale'] = $locale;
    }

    if ($persistent) {
        if (cat_get_site_message_service()->store($message, $locale, $expires)) {
            return;
        }

        error_log('Failed to insert site message, storing in session instead.');
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (headers_sent()) {
            return;
        }

        session_start();
    }

    $messages   = $_SESSION['cat_site_messages'] ?? [];
    $messages[] = $message;

    $_SESSION['cat_site_messages'] = $messages;
    cat_flag_pending_session_messages();
}

/**
 * Cookie path shared with WordPress.
 */
function cat_site_message_cookie_path(): string {
    if (defined('COOKIEPATH') && is_string(COOKIEPATH) && COOKIEPATH !== '') {
        return COOKIEPATH;
    }

    return '/';
}

/**
 * Remember that a flash message is waiting in the PHP session.
 */
function cat_flag_pending_session_messages(): void {
    if (headers_sent()) {
        return;
    }

    setcookie('cat_site_msg', '1', cat_site_message_cookie_options(0));
}

/**
 * Drop the flash-message cookie once the session queue has been read.
 */
function cat_clear_pending_session_messages_flag(): void {
    if (!headers_sent()) {
        setcookie('cat_site_msg', '', cat_site_message_cookie_options(time() - 3600));
    }

    unset($_COOKIE['cat_site_msg']);
}

/**
 * @return array{expires:int,path:string,secure:bool,httponly:bool,samesite:string}
 */
function cat_site_message_cookie_options(int $expires): array {
    return [
        'expires' => $expires,
        'path' => cat_site_message_cookie_path(),
        'secure' => function_exists('is_ssl') && is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

/**
 * Read and empty flash messages without opening a session for every visit.
 *
 * @return array<int, array<string, mixed>>
 */
function cat_consume_session_messages(): array {
    $active = session_status() === PHP_SESSION_ACTIVE;
    $flagged = isset($_COOKIE['cat_site_msg']) && (string) $_COOKIE['cat_site_msg'] !== '';
    if (!$active && !$flagged) {
        return [];
    }

    if (!$active) {
        if (headers_sent()) {
            return [];
        }

        session_start();
    }

    $messages = [];
    if (!empty($_SESSION['cat_site_messages']) && is_array($_SESSION['cat_site_messages'])) {
        $messages = $_SESSION['cat_site_messages'];
        unset($_SESSION['cat_site_messages']);
    }

    cat_clear_pending_session_messages_flag();

    return $messages;
}

/**
 * Remove a persistent site-wide message.
 *
 * @param string $key Message translation key.
 *
 * @return void
 */
function remove_site_message(string $key): void {
    cat_get_site_message_service()->removeByKey($key);
}

/**
 * Retrieve site-wide messages.
 *
 * @return string HTML content for the messages.
 */
function get_site_messages(): string {
    $messages = [];

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_starts_with($uri, '/voir-image-enigme')) {
        return '';
    }
    if (headers_sent()) {
        return '';
    }

    $messages = array_merge($messages, cat_consume_session_messages());

    $service = cat_get_site_message_service();
    $messages = array_merge($messages, $service->getActive());
    $messages = $service->deduplicate($messages);

    if (empty($messages)) {
        return '';
    }

    $output = array_map(
        function (array $msg): string {
            $content = $msg['content'] ?? '';
            if (!empty($msg['message_key'])) {
                if (!empty($msg['locale']) && function_exists('switch_to_locale')) {
                    switch_to_locale($msg['locale']);
                    $content = __($msg['message_key'], 'chassesautresor-com');
                    restore_previous_locale();
                } else {
                    $content = __($msg['message_key'], 'chassesautresor-com');
                }
            }

            $button = '';
            if (!empty($msg['dismissible']) && !empty($msg['message_key'])) {
                $button = ' <button type="button" class="message-close" data-key="'
                    . esc_attr($msg['message_key'])
                    . '" aria-label="'
                    . esc_attr__('Supprimer ce message', 'chassesautresor-com')
                    . '">×</button>';
            }

            $type = $msg['type'] ?? 'info';
            switch ($type) {
                case 'success':
                    $class = 'message-succes';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
                case 'error':
                    $class = 'message-erreur';
                    $aria  = 'role="alert" aria-live="assertive"';
                    break;
                default:
                    $class = 'message-info';
                    $aria  = 'role="status" aria-live="polite"';
                    break;
            }

            return '<p class="' . esc_attr($class) . '" ' . $aria . '>' . esc_html($content) . $button . '</p>';
        },
        $messages
    );

    return implode('', $output);
}

/**
 * Print all site-wide and account-specific messages.
 *
 * @return void
 */
function print_site_messages(): void {
    $messages = get_site_messages();

    if (function_exists('myaccount_get_important_messages')) {
        $messages .= myaccount_get_important_messages();
    }

    echo $messages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
