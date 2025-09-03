<?php
/**
 * Customizes the password reset email.
 *
 * @package chassesautresor-com
 */

defined('ABSPATH') || exit();

/**
 * Filters the password reset email to use the HTML template.
 *
 * @param array   $email      Default email arguments.
 * @param string  $key        Activation key for the reset link.
 * @param string  $user_login Username of the account.
 * @param WP_User $user       User object requesting reset.
 *
 * @return array
 */
function cta_password_reset_notification_email(array $email, string $key, string $user_login, WP_User $user): array
{
    $subject   = esc_html__('Réinitialisation du mot de passe', 'chassesautresor-com');
    $reset_url = function_exists('network_site_url')
        ? network_site_url(
            'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode($user_login),
            'login'
        )
        : '';

    $content  = '<p>' . sprintf(
        esc_html__('Bonjour %s,', 'chassesautresor-com'),
        $user->display_name
    ) . '</p>';
    $content .= '<p>' . esc_html__(
        'Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous '
        . 'pour en créer un nouveau.',
        'chassesautresor-com'
    ) . '</p>';
    $content .= '<p style="margin-top:20px;">';
    $content .= '<a href="' . esc_url($reset_url) . '" ';
    $content .= 'style="display:inline-block;padding:10px 20px;';
    $content .= 'background:#0B132B;color:#ffffff;text-decoration:none;">';
    $content .= esc_html__('Réinitialiser mon mot de passe', 'chassesautresor-com') . '</a></p>';
    $content .= '<p>' . esc_html__(
        "Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.",
        'chassesautresor-com'
    ) . '</p>';

    $email['to']      = $user->user_email;
    $email['subject'] = $subject;
    $email['message'] = cta_render_email_template($subject, $content);

    $headers = $email['headers'] ?? '';
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
    $email['headers'] = implode("\r\n", $headers);

    return $email;
}
add_filter('retrieve_password_notification_email', 'cta_password_reset_notification_email', 10, 4);
