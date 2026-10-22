<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Réglages Discord et Google. Les secrets déjà enregistrés ne sont pas réaffichés.
 */
final class SocialAuthSettingsPage
{
    public static function register(callable $addAction): void
    {
        $addAction('admin_menu', [self::class, 'addPage']);
        $addAction('admin_init', [self::class, 'registerSettings']);
    }

    public static function addPage(): void
    {
        add_options_page(
            __('Connexion Discord et Google', 'chassesautresor-com'),
            __('Connexion sociale', 'chassesautresor-com'),
            'manage_options',
            'chassesautresor-social-auth',
            [self::class, 'render']
        );
    }

    public static function registerSettings(): void
    {
        register_setting(
            'chassesautresor_social_auth',
            SocialAuthSettings::OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default' => (new SocialAuthSettings())->defaults(),
            ]
        );
    }

    /**
     * @param mixed $value
     * @return array<string, string>
     */
    public static function sanitize($value): array
    {
        $incoming = is_array($value) ? $value : [];
        $previous = get_option(SocialAuthSettings::OPTION, []);

        return (new SocialAuthSettings())->sanitize($incoming, is_array($previous) ? $previous : []);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $option = SocialAuthSettings::OPTION;
        $settings = (new SocialAuthService())->storedSettings();
        $callback = cat_social_auth_callback_url();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Connexion Discord et Google', 'chassesautresor-com'); ?></h1>
            <p>
                <?php esc_html_e(
                    'Les boutons s’affichent dès que l’identifiant et le secret sont enregistrés.',
                    'chassesautresor-com'
                ); ?>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields('chassesautresor_social_auth'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('URI de redirection', 'chassesautresor-com'); ?></th>
                        <td>
                            <code><?php echo esc_html($callback); ?></code>
                            <p class="description">
                                <?php esc_html_e(
                                    'À copier telle quelle dans l’application Discord et dans le client OAuth Google.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cat-discord-client-id">
                                <?php esc_html_e('Identifiant client Discord', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                id="cat-discord-client-id"
                                class="regular-text"
                                type="text"
                                name="<?php echo esc_attr($option); ?>[discord_client_id]"
                                value="<?php echo esc_attr($settings['discord_client_id']); ?>"
                                autocomplete="off"
                            >
                            <p class="description">
                                <?php esc_html_e(
                                    'Portée Discord : identify et email.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cat-discord-client-secret">
                                <?php esc_html_e('Secret Discord', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                id="cat-discord-client-secret"
                                class="regular-text"
                                type="password"
                                name="<?php echo esc_attr($option); ?>[discord_client_secret]"
                                value=""
                                autocomplete="new-password"
                                placeholder="<?php esc_attr_e('Laisser vide pour conserver', 'chassesautresor-com'); ?>"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cat-google-client-id">
                                <?php esc_html_e('Identifiant client Google', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                id="cat-google-client-id"
                                class="regular-text"
                                type="text"
                                name="<?php echo esc_attr($option); ?>[google_client_id]"
                                value="<?php echo esc_attr($settings['google_client_id']); ?>"
                                autocomplete="off"
                            >
                            <p class="description">
                                <?php esc_html_e(
                                    'Portée Google : openid, email et profile.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cat-google-client-secret">
                                <?php esc_html_e('Secret Google', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                id="cat-google-client-secret"
                                class="regular-text"
                                type="password"
                                name="<?php echo esc_attr($option); ?>[google_client_secret]"
                                value=""
                                autocomplete="new-password"
                                placeholder="<?php esc_attr_e('Laisser vide pour conserver', 'chassesautresor-com'); ?>"
                            >
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
