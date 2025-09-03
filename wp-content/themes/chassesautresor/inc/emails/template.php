<?php
/**
 * Email template helpers.
 *
 * @package chassesautresor-com
 */

defined('ABSPATH') || exit();

/**
 * Builds the HTML email template.
 *
 * @param string $title   Email title.
 * @param string $content Email body content.
 *
 * @return string
 */
function cta_render_email_template(string $title, string $content): string
{
    $header_bg = '#0B132B';
    $icon_url  = '';

    if (function_exists('get_theme_file_uri')) {
        $icon_url = get_theme_file_uri('assets/images/logo-cat_icone-s.png');
    }

    $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>';
    $html .= '<body style="margin:0;padding:0;">';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" ';
    $html .= 'style="border-collapse:collapse;">';

    $html .= '<tr><td>';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
        . 'class="cta-email-header" style="background:' . esc_attr($header_bg) . ';">';
    $html .= '<tr><td style="padding:20px;text-align:center;">';
    $html .= '<span style="display:inline-block;font-family:Arial,sans-serif;font-size:24px;color:#ffffff;">';
    if ($icon_url) {
        $html .= '<img src="' . esc_url($icon_url) . '" alt="' .
            esc_attr__('Chasses au Trésor', 'chassesautresor-com') . '" ' .
            'style="width:50px;height:50px;vertical-align:middle;margin-right:10px;" />';
    }
    $html .= esc_html($title) . '</span>';
    $html .= '</td></tr></table>';
    $html .= '</td></tr>';

    $html .= '<tr><td>';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" ';
    $html .= 'style="background:#f5f5f5;">';
    $html .= '<tr><td style="padding:20px;font-family:Arial,sans-serif;color:#000000;font-size:16px;">';
    $html .= wp_kses_post($content);
    $html .= '</td></tr></table>';
    $html .= '</td></tr>';

    $html .= '<tr><td>';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
        . 'class="cta-email-footer" style="background:' . esc_attr($header_bg) . ';">';
    $html .= '<tr><td style="padding:20px;text-align:center;font-family:Arial,sans-serif;'
        . 'font-size:12px;color:#ffffff;">';
    $html .= '<a href="' . esc_url('http://chassesautresor.local/mentions-legales/') . '" style="color:#ffffff;">'
        . esc_html__('Mentions légales', 'chassesautresor-com') . '</a>';
    $html .= '<a href="' . esc_url('https://www.chassesautresor.com') . '" '
        . 'style="display:block;margin:10px auto 0;">';
    $logo_url = function_exists('get_theme_file_uri') ? get_theme_file_uri('assets/images/logo-cat_hz-txt.png') : '';
    $html    .= '<img src="' . esc_url($logo_url) . '" alt="'
        . esc_attr__('Chasses au Trésor', 'chassesautresor-com') . '" '
        . 'style="max-width:100%;height:auto;display:block;margin:0 auto;" />';
    $html .= '</a>';
    $html .= '</td></tr></table>';
    $html .= '</td></tr>';

    $html .= '</table>';
    $html .= '</body></html>';

    return $html;
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
function cta_send_email(array|string $to, string $subject, string $body, array $headers = []): bool
{
    $has_content_type = false;
    foreach ($headers as $header) {
        if (0 === stripos($header, 'content-type:')) {
            $has_content_type = true;
            break;
        }
    }

    if (!$has_content_type) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }

    $default_from = '';
    if (function_exists('get_option') && function_exists('get_bloginfo')) {
        $default_from = get_bloginfo('name') . ' <' . get_option('admin_email') . '>';
    }

    $from = function_exists('apply_filters') ? apply_filters('cta_email_from', $default_from) : $default_from;

    $has_from = false;
    foreach ($headers as $index => $header) {
        if (0 === stripos($header, 'from:')) {
            $headers[$index] = 'From: ' . $from;
            $has_from        = true;
            break;
        }
    }

    if (!$has_from && $from) {
        $headers[] = 'From: ' . $from;
    }

    add_filter('wp_mail_content_type', 'cta_set_html_content_type');
    $html   = cta_render_email_template($subject, $body);
    $result = function_exists('wp_mail') ? wp_mail($to, $subject, $html, $headers) : false;

    return $result;
}

/**
 * Forces HTML content type for outgoing emails.
 *
 * @param string $content_type Current content type.
 *
 * @return string
 */
function cta_set_html_content_type(string $content_type = ''): string
{
    if (function_exists('remove_filter')) {
        remove_filter('wp_mail_content_type', 'cta_set_html_content_type');
    }

    return 'text/html; charset=UTF-8';
}

