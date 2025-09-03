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
    $logo_html = '';

    if (function_exists('get_theme_mod')) {
        $logo_id = get_theme_mod('custom_logo');

        if ($logo_id && function_exists('wp_get_attachment_image_url')) {
            $src = wp_get_attachment_image_url($logo_id, 'full');

            if ($src) {
                $logo_html = sprintf(
                    '<img src="%s" alt="%s" style="max-height:60px;height:auto;display:block;margin:0 auto;" />',
                    esc_url($src),
                    esc_attr(function_exists('get_bloginfo') ? get_bloginfo('name') : '')
                );
            }
        }
    }

    $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>';
    $html .= '<body style="margin:0;padding:0;">';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" ';
    $html .= 'style="border-collapse:collapse;">';

    $html .= '<tr><td>';
    $html .= '<header style="background:' . esc_attr($header_bg) . ';padding:20px;text-align:center;">';
    if ($logo_html) {
        $html .= $logo_html;
    }
    $html .= '<h1 style="margin:0;color:#ffffff;font-family:Arial,sans-serif;font-size:24px;">' .
        esc_html($title) . '</h1>';
    $html .= '</header>';
    $html .= '</td></tr>';

    $html .= '<tr><td>';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" ';
    $html .= 'style="background:#f5f5f5;">';
    $html .= '<tr><td style="padding:20px;font-family:Arial,sans-serif;color:#000000;font-size:16px;">';
    $html .= wp_kses_post($content);
    $html .= '</td></tr></table>';
    $html .= '</td></tr>';

    $html .= '<tr><td>';
    $html .= '<footer style="background:' . esc_attr($header_bg) . ';padding:20px;text-align:center;';
    $html .= 'font-family:Arial,sans-serif;font-size:12px;color:#ffffff;">';
    $html .= '<a href="' . esc_url(home_url('/mentions-legales/')) . '" style="color:#ffffff;">' .
        esc_html__('Mentions légales', 'chassesautresor-com') . '</a> | ';
    $html .= '<a href="' . esc_url(home_url('/?unsubscribe=1')) . '" style="color:#ffffff;">' .
        esc_html__('Se désabonner', 'chassesautresor-com') . '</a>';
    $html .= '</footer>';
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

    $html = cta_render_email_template($subject, $body);

    return function_exists('wp_mail') ? wp_mail($to, $subject, $html, $headers) : false;
}

