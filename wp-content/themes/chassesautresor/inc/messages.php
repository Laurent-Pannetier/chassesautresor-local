<?php

defined('ABSPATH') || exit;

/**
 * Store a site-wide message.
 *
 * @param string $type       Message type used as CSS class.
 * @param string $content    Message content.
 * @param bool   $persistent Whether the message should persist across sessions.
 *
 * @return void
 */
function add_site_message(string $type, string $content, bool $persistent = false): void
{
    $message = [
        'type'    => $type,
        'content' => $content,
    ];

    if ($persistent) {
        $messages = get_transient('cat_site_messages');
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = $message;
        set_transient('cat_site_messages', $messages, 0);
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

    if (empty($messages)) {
        return '';
    }

    $output = array_map(
        function (array $msg): string {
            return '<p class="' . esc_attr($msg['type']) . '">' . esc_html($msg['content']) . '</p>';
        },
        $messages
    );

    return implode('', $output);
}

