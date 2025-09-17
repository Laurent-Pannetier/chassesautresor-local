<?php
/**
 * Organisation overview for organizer roles.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$current_user  = wp_get_current_user();
$user_id       = (int) ($current_user->ID ?? 0);
$user_roles    = (array) ($current_user->roles ?? []);
$allowed_roles = array(
    defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur',
    defined('ROLE_ORGANISATEUR_CREATION') ? ROLE_ORGANISATEUR_CREATION : 'organisateur_creation',
);

if (!$user_id || !array_intersect($allowed_roles, $user_roles)) {
    wp_safe_redirect(home_url('/mon-compte/'));
    exit;
}

$organisateur_id = function_exists('get_organisateur_from_user')
    ? (int) get_organisateur_from_user($user_id)
    : 0;

if (!$organisateur_id) {
    echo '<p class="myaccount-placeholder">' .
        esc_html__('Aucune organisation associée pour le moment.', 'chassesautresor-com') .
        '</p>';
    return;
}

$players_total = 0;
if (function_exists('organisateur_compter_joueurs_uniques')) {
    $players_total = organisateur_compter_joueurs_uniques($organisateur_id);
}

$manage_url = '';
$organizer_permalink = get_permalink($organisateur_id);
if ($organizer_permalink) {
    $manage_url = add_query_arg(
        array(
            'edition' => 'open',
            'onglet'  => 'revenus',
        ),
        $organizer_permalink
    );
}
?>
<div class="dashboard-grid stats-cards myaccount-organisation-cards">
    <?php
    get_template_part(
        'template-parts/common/stat-card',
        null,
        array(
            'icon'  => 'fa-solid fa-users',
            'label' => __('Nombre de joueurs', 'chassesautresor-com'),
            'value' => number_format_i18n($players_total),
            'stat'  => 'organizer-players',
        )
    );
    ?>
    <div class="dashboard-card" data-stat="organizer-points">
        <i class="fa-solid fa-landmark" aria-hidden="true"></i>
        <h3><?php esc_html_e('Points de mon organisation', 'chassesautresor-com'); ?></h3>
        <?php if ($manage_url) : ?>
        <a class="stat-value" href="<?php echo esc_url($manage_url); ?>">
            <?php esc_html_e('Gérer', 'chassesautresor-com'); ?>
        </a>
        <?php else : ?>
        <p class="stat-value"><?php esc_html_e('Indisponible', 'chassesautresor-com'); ?></p>
        <?php endif; ?>
    </div>
</div>
