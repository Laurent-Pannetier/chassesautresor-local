<?php

defined('ABSPATH') || exit;

/**
 * Store a site-wide message.
 *
 * @param string      $type        Message type used as CSS class.
 * @param string      $content     Message content.
 * @param bool        $persistent  Whether the message should persist across sessions.
 * @param string|null $message_key Optional translation key.
 * @param string|null $locale      Optional locale for the message.
 * @param int|null    $expires     Expiration as timestamp or duration in seconds.
 * @param string|null $key         Optional identifier used for dismissible messages.
 * @param bool        $dismissible Whether the user can dismiss the message.
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
    ?string $key = null,
    bool $dismissible = false
): void
{
    $message = [
        'type'    => $type,
        'content' => $content,
    ];

    if ($message_key !== null) {
        $message['message_key'] = $message_key;
    }

    if ($locale !== null) {
        $message['locale'] = $locale;
    }

    if ($key !== null) {
        $message['key'] = $key;
    }

    if ($dismissible) {
        $message['dismissible'] = true;
    }

    if ($persistent) {
        $messages = get_transient('cat_site_messages');
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = $message;

        $expirationSeconds = 0;
        $expiresAt         = null;
        if ($expires !== null) {
            if ($expires > time()) {
                $expirationSeconds = $expires - time();
                $expiresAt         = gmdate('c', $expires);
            } else {
                $expirationSeconds = $expires;
                $expiresAt         = gmdate('c', time() + $expires);
            }
        }

        set_transient('cat_site_messages', $messages, $expirationSeconds);

        global $wpdb;
        $repo = new UserMessageRepository($wpdb);
        $repo->insert(0, wp_json_encode($message), 'site', $expiresAt, $locale);
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $messages   = $_SESSION['cat_site_messages'] ?? [];
    $messages[] = $message;

    $_SESSION['cat_site_messages'] = $messages;
}

/**
 * Retrieve site-wide messages.
 *
 * @return string HTML content for the messages.
 */
function get_site_messages(): string
{
    $messages = [];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!empty($_SESSION['cat_site_messages'])) {
        $messages = array_merge($messages, $_SESSION['cat_site_messages']);
        unset($_SESSION['cat_site_messages']);
    }

    $transient = get_transient('cat_site_messages');
    if (is_array($transient) && !empty($transient)) {
        $messages = array_merge($messages, $transient);
    }

    global $wpdb;
    $repo = new UserMessageRepository($wpdb);
    $rows = $repo->get(0, 'site', false);
    foreach ($rows as $row) {
        $data = json_decode($row['message'], true);
        if (is_array($data)) {
            if (!empty($row['locale'])) {
                $data['locale'] = $row['locale'];
            }
            $messages[] = $data;
        }
    }

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
            $dismissible = !empty($msg['dismissible']) && !empty($msg['key']);
            $button      = '';
            if ($dismissible) {
                $button = ' <button type="button" class="message-close" data-key="'
                    . esc_attr($msg['key'])
                    . '" aria-label="'
                    . esc_attr__('Supprimer ce message', 'chassesautresor-com')
                    . '">×</button>';
            }

            return '<p class="' . esc_attr($msg['type']) . '">' . esc_html($content) . $button . '</p>';
        },
        $messages
    );

    return implode('', $output);
}

