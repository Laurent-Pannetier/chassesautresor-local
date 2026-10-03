<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Site;

final class SiteExperienceSettingsPage
{
    public static function register(callable $addAction): void
    {
        $addAction('admin_menu', [self::class, 'addPage']);
        $addAction('admin_init', [self::class, 'registerSettings']);
    }

    public static function addPage(): void
    {
        add_options_page(
            __('Expérience du site', 'chassesautresor-com'),
            __('Expérience du site', 'chassesautresor-com'),
            'manage_options',
            'chassesautresor-site-experience',
            [self::class, 'render']
        );
    }

    public static function registerSettings(): void
    {
        register_setting(
            'chassesautresor_site_experience',
            SiteExperienceService::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default' => [
                    'mode' => SiteExperienceService::MODE_SINGLE_HUNT,
                    'primary_hunt_id' => 0,
                    'organizer_applications_open' => 0,
                ],
            ]
        );
    }

    /**
     * @param mixed $value
     *
     * @return array{mode:string,primary_hunt_id:int,organizer_applications_open:int}
     */
    public static function sanitize($value): array
    {
        $settings = is_array($value) ? $value : [];

        return (new SiteExperienceService())->sanitize(
            $settings,
            static fn(int $postId): bool => get_post_type($postId) === 'chasse'
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = cat_get_site_experience_settings();
        $service = new SiteExperienceService();
        $optionName = SiteExperienceService::OPTION_NAME;
        $singleHuntMode = $service->isSingleHuntMode($settings);
        $mode = (string) ($settings['mode'] ?? SiteExperienceService::MODE_SINGLE_HUNT);
        $primaryHuntId = $service->getPrimaryHuntId($settings);
        $hunts = get_posts([
            'post_type' => 'chasse',
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Expérience du site', 'chassesautresor-com'); ?></h1>
            <p>
                <?php esc_html_e(
                    'Choisissez la présentation publique sans supprimer les capacités métier du moteur.',
                    'chassesautresor-com'
                ); ?>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields('chassesautresor_site_experience'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Mode du site', 'chassesautresor-com'); ?></th>
                        <td>
                            <label>
                                <input
                                    type="radio"
                                    name="<?php echo esc_attr($optionName); ?>[mode]"
                                    value="single_hunt"
                                    <?php checked($mode, SiteExperienceService::MODE_SINGLE_HUNT); ?>
                                >
                                <?php esc_html_e('Chasse unique', 'chassesautresor-com'); ?>
                            </label><br>
                            <label>
                                <input
                                    type="radio"
                                    name="<?php echo esc_attr($optionName); ?>[mode]"
                                    value="demo"
                                    <?php checked($mode, SiteExperienceService::MODE_DEMO); ?>
                                >
                                <?php esc_html_e('Démo ou prévisualisation', 'chassesautresor-com'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e(
                                    'Affiche la chasse principale sur l’accueil même si elle est encore en édition.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                            <label>
                                <input
                                    type="radio"
                                    name="<?php echo esc_attr($optionName); ?>[mode]"
                                    value="platform"
                                    <?php checked($mode, SiteExperienceService::MODE_PLATFORM); ?>
                                >
                                <?php esc_html_e('Plateforme', 'chassesautresor-com'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cat-primary-hunt">
                                <?php esc_html_e('Chasse principale', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <select
                                id="cat-primary-hunt"
                                name="<?php echo esc_attr($optionName); ?>[primary_hunt_id]"
                            >
                                <option value="0">
                                    <?php esc_html_e('Sélection automatique temporaire', 'chassesautresor-com'); ?>
                                </option>
                                <?php foreach ($hunts as $hunt) : ?>
                                    <option
                                        value="<?php echo esc_attr((string) $hunt->ID); ?>"
                                        <?php selected($primaryHuntId, (int) $hunt->ID); ?>
                                    >
                                        <?php echo esc_html($hunt->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e(
                                    'À défaut, la dernière chasse publiée et validée est utilisée.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Candidatures organisateur', 'chassesautresor-com'); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr($optionName); ?>[organizer_applications_open]"
                                    value="1"
                                    <?php checked($service->areOrganizerApplicationsOpen($settings)); ?>
                                    <?php disabled($singleHuntMode); ?>
                                >
                                <?php
                                esc_html_e(
                                    'Autoriser de nouvelles candidatures en mode plateforme',
                                    'chassesautresor-com'
                                );
                                ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e(
                                    'Les candidatures restent toujours fermées en mode chasse unique.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
