<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntLifecycleApplicationService;
use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Site\SiteExperienceService;

if (!function_exists('cat_get_managed_hunt_id_for_user')) {
    /**
     * Resolve the hunt managed from the account home for the current actor.
     */
    function cat_get_managed_hunt_id_for_user(int $userId = 0): int
    {
        $userId = $userId > 0 ? $userId : (int) get_current_user_id();
        $primary = function_exists('cat_get_primary_hunt_id') ? cat_get_primary_hunt_id() : 0;
        if ($primary > 0) {
            return $primary;
        }

        if ($userId <= 0 || !function_exists('get_organisateur_from_user')) {
            return 0;
        }

        $organizerId = (int) get_organisateur_from_user($userId);
        if ($organizerId <= 0 || !function_exists('get_chasses_de_organisateur')) {
            return 0;
        }

        $hunts = get_chasses_de_organisateur($organizerId);
        if ($hunts instanceof WP_Query && !empty($hunts->posts)) {
            return (int) $hunts->posts[0];
        }
        if (is_array($hunts) && $hunts !== []) {
            return (int) $hunts[0];
        }

        return 0;
    }
}

if (!function_exists('cat_get_hunt_lifecycle_view')) {
    /**
     * @return array<string, mixed>|null
     */
    function cat_get_hunt_lifecycle_view(int $huntId = 0, int $userId = 0): ?array
    {
        $userId = $userId > 0 ? $userId : (int) get_current_user_id();
        $huntId = $huntId > 0 ? $huntId : cat_get_managed_hunt_id_for_user($userId);
        if ($huntId <= 0 || $userId <= 0) {
            return null;
        }

        $isDemo = (new SiteExperienceService())->isDemoMode(cat_get_site_experience_settings());

        return (new HuntLifecycleApplicationService())->getViewModel($huntId, $userId, $isDemo);
    }
}

if (!function_exists('cat_get_hunt_quick_edit_links')) {
    /**
     * @return array<int, array{label:string,url:string}>
     */
    function cat_get_hunt_quick_edit_links(int $huntId = 0): array
    {
        $huntId = $huntId > 0 ? $huntId : cat_get_managed_hunt_id_for_user();
        if ($huntId <= 0) {
            return [];
        }

        $links = [];
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId)) ?? 0;

        $showOrganizerLink = $organizerId > 0
            && (
                !function_exists('cat_is_single_hunt_mode')
                || !cat_is_single_hunt_mode()
                || current_user_can('manage_options')
            );
        if ($showOrganizerLink) {
            $links[] = [
                'label' => __('Organisateur', 'chassesautresor-com'),
                'url' => add_query_arg(['edition' => 'open'], get_permalink($organizerId)),
            ];
        }

        $links[] = [
            'label' => __('Chasse', 'chassesautresor-com'),
            'url' => add_query_arg(['edition' => 'open', 'tab' => 'param'], get_permalink($huntId)),
        ];

        $riddleIds = array_map(
            'intval',
            (array) get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId))
        );
        foreach ($riddleIds as $riddleId) {
            $title = get_the_title($riddleId);
            $links[] = [
                'label' => $title !== '' ? $title : __('Énigme', 'chassesautresor-com'),
                'url' => add_query_arg(['edition' => 'open', 'tab' => 'param'], get_permalink($riddleId)),
            ];
        }

        return $links;
    }
}

if (!function_exists('cat_render_hunt_lifecycle_switch')) {
    function cat_render_hunt_lifecycle_switch(int $huntId = 0): string
    {
        $view = cat_get_hunt_lifecycle_view($huntId);
        if ($view === null) {
            return '';
        }

        ob_start();
        ?>
        <div
            class="dashboard-card hunt-lifecycle-card"
            data-hunt-lifecycle
            data-hunt-id="<?php echo esc_attr((string) $view['hunt_id']); ?>"
            data-state="<?php echo esc_attr($view['state']); ?>"
        >
            <div class="dashboard-card-header">
                <i class="fas fa-toggle-on" aria-hidden="true"></i>
                <h3><?php esc_html_e('Éditer / Activer', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="dashboard-card-content hunt-lifecycle-card__content">
                <div class="hunt-lifecycle-card__control">
                    <span class="hunt-lifecycle-card__label-off"><?php esc_html_e('Éditer', 'chassesautresor-com'); ?></span>
                    <label class="switch-control">
                        <input
                            type="checkbox"
                            data-hunt-lifecycle-toggle
                            <?php checked(!empty($view['checked'])); ?>
                            <?php disabled(!empty($view['disabled'])); ?>
                        >
                        <span class="switch-slider"></span>
                    </label>
                    <span class="hunt-lifecycle-card__label-on"><?php esc_html_e('Activer', 'chassesautresor-com'); ?></span>
                </div>
                <p class="hunt-lifecycle-card__status" data-hunt-lifecycle-status>
                    <?php echo esc_html((string) $view['status_label']); ?>
                </p>
                <?php if (!empty($view['help'])) : ?>
                    <p class="hunt-lifecycle-card__help" data-hunt-lifecycle-help>
                        <?php echo esc_html((string) $view['help']); ?>
                    </p>
                <?php else : ?>
                    <p class="hunt-lifecycle-card__help" data-hunt-lifecycle-help hidden></p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}

if (!function_exists('cat_render_hunt_quick_edit_card')) {
    function cat_render_hunt_quick_edit_card(int $huntId = 0): string
    {
        $links = cat_get_hunt_quick_edit_links($huntId);
        if ($links === []) {
            return '';
        }

        ob_start();
        ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                <h3><?php esc_html_e('Accès rapide édition', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="dashboard-card-content">
                <ul class="hunt-quick-edit-list">
                    <?php foreach ($links as $link) : ?>
                        <li>
                            <a href="<?php echo esc_url($link['url']); ?>">
                                <?php echo esc_html($link['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
