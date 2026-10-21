<?php
/**
 * Existing organizer tree, kept in the shell sidebar.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
if (!$current_user instanceof WP_User || (int) $current_user->ID <= 0) {
    return;
}

$organizer_roles = ['organisateur', 'organisateur_creation'];
if (defined('ROLE_ORGANISATEUR')) {
    $organizer_roles[] = ROLE_ORGANISATEUR;
}
if (defined('ROLE_ORGANISATEUR_CREATION')) {
    $organizer_roles[] = ROLE_ORGANISATEUR_CREATION;
}

if (!array_intersect($organizer_roles, (array) $current_user->roles)) {
    return;
}

$organizer_nav = function_exists('myaccount_get_organizer_nav')
    ? myaccount_get_organizer_nav((int) $current_user->ID)
    : null;

if ($organizer_nav && function_exists('myaccount_render_organizer_nav')) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo myaccount_render_organizer_nav($organizer_nav);
    return;
}

$organizer_id = function_exists('get_organisateur_from_user')
    ? (int) get_organisateur_from_user((int) $current_user->ID)
    : 0;

if (!$organizer_id || !function_exists('get_the_title')) {
    return;
}

$show_organizer_link = !function_exists('cat_is_platform_mode') || cat_is_platform_mode();
$organisation_title = get_the_title($organizer_id);
$organisation_url = function_exists('get_permalink') ? get_permalink($organizer_id) : '';
$organizer_hunt_ids = [];

if (function_exists('get_chasses_de_organisateur')) {
    $hunts_query = get_chasses_de_organisateur($organizer_id);
    if ($hunts_query instanceof WP_Query) {
        $organizer_hunt_ids = array_map('intval', $hunts_query->posts);
    } elseif (is_array($hunts_query)) {
        $organizer_hunt_ids = array_map('intval', $hunts_query);
    }
}

if (function_exists('chasse_est_visible_pour_utilisateur')) {
    $organizer_hunt_ids = array_values(array_filter(
        $organizer_hunt_ids,
        static function ($hunt_id) use ($current_user) {
            return chasse_est_visible_pour_utilisateur((int) $hunt_id, (int) $current_user->ID);
        }
    ));
}

$organizer_hunts = [];
if (!empty($organizer_hunt_ids) && function_exists('get_permalink')) {
    foreach ($organizer_hunt_ids as $hunt_id) {
        $hunt_title = get_the_title($hunt_id);
        $hunt_link = get_permalink($hunt_id);
        if ($hunt_link && function_exists('cat_is_single_hunt_mode') && cat_is_single_hunt_mode()) {
            $hunt_link = function_exists('cat_single_hunt_enigmas_url')
                ? cat_single_hunt_enigmas_url()
                : home_url('/#enigmes');
        }
        if ($hunt_title && $hunt_link) {
            $organizer_hunts[] = [
                'title' => $hunt_title,
                'url'   => $hunt_link,
            ];
        }
    }
}

if ((!$show_organizer_link || $organisation_title === '' || $organisation_url === '') && $organizer_hunts === []) {
    return;
}
?>
<nav
    class="dashboard-nav organizer-organisation-nav<?php
        echo $show_organizer_link ? '' : ' organizer-nav--hunt-first';
    ?>"
    aria-label="<?php esc_attr_e('Organisation', 'chassesautresor-com'); ?>"
>
    <ul class="organizer-nav-tree">
        <?php if ($show_organizer_link && $organisation_title && $organisation_url) : ?>
            <li class="organizer-nav-item organizer-nav-root">
                <a href="<?php echo esc_url($organisation_url); ?>" class="dashboard-nav-link organizer-nav-link">
                    <i class="fas fa-landmark" aria-hidden="true"></i>
                    <span><?php echo esc_html($organisation_title); ?></span>
                </a>
                <?php if ($organizer_hunts !== []) : ?>
                    <ul class="organizer-nav-children">
                        <?php foreach ($organizer_hunts as $organizer_hunt) : ?>
                            <li class="organizer-nav-item">
                                <a
                                    href="<?php echo esc_url($organizer_hunt['url']); ?>"
                                    class="dashboard-nav-link organizer-nav-link organizer-nav-hunt"
                                >
                                    <span class="nav-title"><?php echo esc_html($organizer_hunt['title']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php else : ?>
            <?php foreach ($organizer_hunts as $organizer_hunt) : ?>
                <li class="organizer-nav-item">
                    <a
                        href="<?php echo esc_url($organizer_hunt['url']); ?>"
                        class="dashboard-nav-link organizer-nav-link organizer-nav-hunt"
                    >
                        <span class="nav-title"><?php echo esc_html($organizer_hunt['title']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</nav>
