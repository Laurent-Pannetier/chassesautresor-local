<?php
/**
 * Customizes the password reset email.
 *
 * @package chassesautresor-com
 */

defined('ABSPATH') || exit();

/**
 * Filters the password reset notification email to use the HTML template.
 *
 * @param array   $email      Default email arguments.
 * @param string  $key        Password reset key.
 * @param string  $user_login The user login.
 * @param WP_User $user       User object.
 *
 * @return array
 */
function cta_password_reset_notification_email(array $email, string $key, string $user_login, $user): array
{
    $blogname = function_exists('get_option') ? wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES) : '';
    $subject  = esc_html__('Réinitialisation du mot de passe', 'chassesautresor-com');
    if ($blogname) {
        $subject = '[' . $blogname . '] ' . $subject;
    }

    $reset_url = function_exists('network_site_url')
        ? network_site_url('wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode($user->user_login), 'login')
        : '';

    $content  = '<p>' . sprintf(
        /* translators: %s: User display name */
        esc_html__('Bonjour %s,', 'chassesautresor-com'),
        $user->display_name
    ) . '</p>';
    $content .= '<p>' . esc_html__(
        'Quelqu’un a demandé la réinitialisation du mot de passe de votre compte.',
        'chassesautresor-com'
    ) . '</p>';
    $content .= '<p>' . esc_html__(
        'Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail et aucun changement ne sera effectué.',
        'chassesautresor-com'
    ) . '</p>';
    $content .= '<p style="margin-top:20px;"><a href="' . esc_url($reset_url) . '" style="display:inline-block;padding:10px 20px;background:#0B132B;color:#ffffff;text-decoration:none;">' . esc_html__('Réinitialiser mon mot de passe', 'chassesautresor-com') . '</a></p>';

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip_address) {
        $content .= '<p style="font-size:12px;color:#555;">' . sprintf(
            /* translators: %s: IP address */
            esc_html__('Demande effectuée depuis l’adresse IP %s.', 'chassesautresor-com'),
            esc_html($ip_address)
        ) . '</p>';
    }

    $email['to']      = $user->user_email;
    $email['subject'] = $subject;
    $email['message'] = cta_render_email_template($subject, $content);

    $headers = $email['headers'] ?? [];
    if (!is_array($headers)) {
        $headers = $headers ? preg_split("/(\r\n|\r|\n)/", (string) $headers) : [];
    }
    $has_type = false;
    foreach ($headers as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            $has_type = true;
            break;
        }
    }
    if (!$has_type) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }
    $email['headers'] = $headers;

    return $email;
}
add_filter('retrieve_password_notification_email', 'cta_password_reset_notification_email', 10, 4);
