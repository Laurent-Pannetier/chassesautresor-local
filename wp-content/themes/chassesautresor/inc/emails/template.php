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
    $logo_url = '';
    if (function_exists('get_theme_mod')) {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id && function_exists('wp_get_attachment_image_url')) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        }
    }

    if (!$logo_url && function_exists('get_theme_file_uri')) {
        $logo_url = get_theme_file_uri('assets/images/logo.png');
    }

    $title_icon = function_exists('get_theme_file_uri') ? get_theme_file_uri('assets/images/logo-cat_icone-s.png') : '';
    $footer_logo = function_exists('get_theme_file_uri') ? get_theme_file_uri('assets/images/logo-cat_hz-txt.png') : '';
    $site_name   = function_exists('get_bloginfo') ? get_bloginfo('name') : '';
    $header_bg   = '#0B132B';

    $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" ';
    $html .= 'style="border-collapse:collapse;">';
    $html .= '<tr><td>';
    $html .= '<header style="background:' . esc_attr($header_bg) . ';padding:20px;text-align:center;">';
    if ($logo_url) {
        $html .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" ';
        $html .= 'style="max-width:150px;height:auto;display:block;margin:0 auto 10px;" />';
    }
    $html .= '<h1 style="color:#ffffff;font-family:Arial,sans-serif;font-size:24px;margin:0;">';
    if ($title_icon) {
        $html .= '<img src="' . esc_url($title_icon) . '" alt="" style="height:24px;width:24px;vertical-align:middle;margin-right:8px;" />';
    }
    $html .= esc_html($title) . '</h1>';
    $html .= '</header>';
    $html .= '</td></tr>';
    $html .= '<tr><td style="background:#f5f5f5;padding:20px;font-family:Arial,sans-serif;">';
    $html .= wp_kses_post($content);
    $html .= '<p style="margin-top:20px;">' . esc_html__('L\'équipe chassesautresor.com', 'chassesautresor-com') . '</p>';
    $html .= '</td></tr>';
    $html .= '<tr><td>';
    $html .= '<footer style="background:' . esc_attr($header_bg) . ';padding:20px;text-align:center;';
    $html .= 'font-family:Arial,sans-serif;color:#ffffff;font-size:12px;">';
    if ($footer_logo) {
        $html .= '<p style="margin:0;"><a href="https://chassesautresor.com" style="display:inline-block;">';
        $html .= '<img src="' . esc_url($footer_logo) . '" alt="chassesautresor.com" style="max-width:150px;height:auto;" />';
        $html .= '</a></p>';
    }
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
