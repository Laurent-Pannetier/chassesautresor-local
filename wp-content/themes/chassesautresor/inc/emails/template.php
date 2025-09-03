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
function cta_render_email_template( string $title, string $content ): string {
    $logo_url = '';
    if ( function_exists( 'get_theme_mod' ) ) {
        $logo_id = get_theme_mod( 'custom_logo' );
        if ( $logo_id && function_exists( 'wp_get_attachment_image_url' ) ) {
            $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
        }
    }

    if ( ! $logo_url && function_exists( 'get_theme_file_uri' ) ) {
        $logo_url = get_theme_file_uri( 'assets/images/logo.png' );
    }

    $title_icon  = function_exists( 'get_theme_file_uri' ) ? get_theme_file_uri( 'assets/images/logo-cat_icone-s.png' ) : '';
    $footer_logo = function_exists( 'get_theme_file_uri' ) ? get_theme_file_uri( 'assets/images/logo-cat_hz-txt.png' ) : '';
    $site_name   = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '';
    $header_bg   = '#0B132B';
    $team_label  = function_exists( 'esc_html__' )
        ? esc_html__( "L'équipe chassesautresor.com", 'chassesautresor-com' )
        : "L'équipe chassesautresor.com";

    $content = function_exists( 'wp_kses_post' ) ? wp_kses_post( $content ) : $content;

    $loader = new \Twig\Loader\FilesystemLoader( __DIR__ . '/templates' );
    $twig   = new \Twig\Environment( $loader );

    return $twig->render(
        'email.twig',
        [
            'title'       => $title,
            'content'     => $content,
            'logo_url'    => $logo_url,
            'title_icon'  => $title_icon,
            'footer_logo' => $footer_logo,
            'site_name'   => $site_name,
            'header_bg'   => $header_bg,
            'team_label'  => $team_label,
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
