<?php
/**
 * Email template helpers.
 *
 * @package chassesautresor-com
 */

defined('ABSPATH') || exit();

/**
 * Renders the HTML email template.
 *
 * @param string $title   Email title.
 * @param string $content Email body content.
 *
 * @return string
 */
function cta_render_email_template(string $title, string $content): string
{
    $content = function_exists('wp_kses_post') ? wp_kses_post($content) : $content;

    if (!class_exists('\\Twig\\Loader\\FilesystemLoader')) {
        $autoloader = dirname(__DIR__, 5) . '/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }
    }

    if (!class_exists('\\Twig\\Loader\\FilesystemLoader')) {
        $title_html = function_exists('esc_html') ? esc_html($title) : $title;
        return '<h1>' . $title_html . '</h1>' . $content;
    }

    $loader = new \\Twig\\Loader\\FilesystemLoader(__DIR__ . '/templates');
    $twig   = new \\Twig\\Environment($loader);

    if (function_exists('get_theme_file_uri')) {
        $twig->addFunction(new \Twig\TwigFunction('get_theme_file_uri', 'get_theme_file_uri'));
    }

    if (function_exists('home_url')) {
        $twig->addFunction(new \Twig\TwigFunction('home_url', 'home_url'));
    }

    if (function_exists('__')) {
        $twig->addFunction(new \Twig\TwigFunction('__', '__'));
    }

    $mentions_url = function_exists('home_url') ? home_url('/mentions-legales/') : '';
    $home_url     = function_exists('home_url') ? home_url('/') : '';

    return $twig->render(
        'email.twig',
        [
            'title'        => $title,
            'content'      => $content,
            'mentions_url' => $mentions_url,
            'home_url'     => $home_url,
        ]
    );
}

/**
 * Sends an HTML email using the template.
 *
 * @param array|string $to      Recipient or list of recipients.
 * @param string       $subject Email subject.
 * @param string       $body    Email body content.
 * @param array        $headers Additional headers.
 *
 * @return bool
 */
function cta_send_email($to, string $subject, string $body, array $headers = []): bool
{
    $has_content_type = false;
    foreach ($headers as $header) {
        if (0 === stripos($header, 'Content-Type:')) {
            $has_content_type = true;
            break;
        }
    }

    if (!$has_content_type) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }

    $from = function_exists('apply_filters') ? apply_filters('cta_send_email_from', '') : '';
    if ($from) {
        foreach ($headers as $index => $header) {
            if (0 === stripos($header, 'From:')) {
                unset($headers[$index]);
            }
        }
        $headers[] = 'From: ' . $from;
    }

    $html = cta_render_email_template($subject, $body);

    return function_exists('wp_mail') ? wp_mail($to, $subject, $html, $headers) : false;
}
