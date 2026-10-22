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
        $addAction('admin_notices', [self::class, 'notice']);
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
        $gate = new SocialAuthSettings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Connexion Discord et Google', 'chassesautresor-com'); ?></h1>
            <p>
                <?php esc_html_e(
                    'Sans identifiant et secret, le clic s’arrête avant Discord ou Google.',
                    'chassesautresor-com'
                ); ?>
            </p>
            <ul>
                <li>
                    <?php echo esc_html(self::statusLine($gate, $settings, 'discord', 'Discord')); ?>
                </li>
                <li>
                    <?php echo esc_html(self::statusLine($gate, $settings, 'google', 'Google')); ?>
                </li>
            </ul>
            <p>
                <?php esc_html_e(
                    'Après l’enregistrement, le champ secret reste vide. Le secret sauvé n’est pas réaffiché.',
                    'chassesautresor-com'
                ); ?>
            </p>
            <?php self::sourceHelp(); ?>
            <form method="post" action="options.php">
                <?php settings_fields('chassesautresor_social_auth'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('URI de redirection', 'chassesautresor-com'); ?></th>
                        <td>
                            <code><?php echo esc_html($callback); ?></code>
                            <p class="description">
                                <?php esc_html_e(
                                    'À copier telle quelle chez Discord et dans le client OAuth Google.',
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

    public static function notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if ($page === 'chassesautresor-social-auth') {
            return;
        }

        $settings = (new SocialAuthService())->storedSettings();
        $gate = new SocialAuthSettings();
        $missing = [];
        foreach (['discord' => 'Discord', 'google' => 'Google'] as $provider => $label) {
            if (!$gate->ready($settings, $provider)) {
                $missing[] = $label;
            }
        }
        if ($missing === []) {
            return;
        }

        $url = admin_url('options-general.php?page=chassesautresor-social-auth');
        echo '<div class="notice notice-warning"><p>';
        echo esc_html(sprintf(
            /* translators: %s: Discord, Google, or both. */
            __('La connexion %s ne peut pas démarrer : identifiant ou secret manquant.', 'chassesautresor-com'),
            implode(' et ', $missing)
        ));
        echo ' <a href="' . esc_url($url) . '">';
        esc_html_e('Ouvrir Connexion sociale', 'chassesautresor-com');
        echo '</a></p></div>';
    }

    private static function sourceHelp(): void
    {
        echo '<h2>' . esc_html__('Où trouver ces informations', 'chassesautresor-com') . '</h2>';
        echo '<ol>';
        echo '<li>';
        esc_html_e(
            'Discord : créez une application, ouvrez OAuth2, copiez Client ID et Client Secret.',
            'chassesautresor-com'
        );
        echo ' ';
        self::sourceLink(
            'https://discord.com/developers/applications',
            __('Portail Discord', 'chassesautresor-com')
        );
        echo '</li><li>';
        esc_html_e(
            'Google : projet Cloud, écran de consentement, puis un client OAuth « Application Web ».',
            'chassesautresor-com'
        );
        echo ' ';
        self::sourceLink(
            'https://console.cloud.google.com/apis/credentials',
            __('Identifiants Google', 'chassesautresor-com')
        );
        echo '</li></ol>';
        echo '<p class="description">';
        esc_html_e(
            'Collez l’URI telle quelle. En mode Test, ajoutez votre compte Google comme utilisateur test.',
            'chassesautresor-com'
        );
        echo '</p>';
    }

    private static function sourceLink(string $url, string $label): void
    {
        echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">';
        echo esc_html($label);
        echo '</a>';
    }

    /**
     * @param array<string, string> $settings
     */
    private static function statusLine(
        SocialAuthSettings $gate,
        array $settings,
        string $provider,
        string $label
    ): string {
        $gap = $gate->gap($settings, $provider);
        if ($gap === '') {
            return sprintf(
                /* translators: %s: Discord or Google. */
                __('%s : prêt, identifiant et secret enregistrés.', 'chassesautresor-com'),
                $label
            );
        }
        if ($gap === 'secret') {
            return sprintf(
                /* translators: %s: Discord or Google. */
                __('%s : identifiant enregistré, secret manquant.', 'chassesautresor-com'),
                $label
            );
        }
        if ($gap === 'id') {
            return sprintf(
                /* translators: %s: Discord or Google. */
                __('%s : secret enregistré, identifiant manquant.', 'chassesautresor-com'),
                $label
            );
        }

        return sprintf(
            /* translators: %s: Discord or Google. */
            __('%s : identifiant et secret manquants.', 'chassesautresor-com'),
            $label
        );
    }
}
