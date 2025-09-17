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
<?php
$chasses_query = function_exists('get_chasses_de_organisateur')
    ? get_chasses_de_organisateur($organisateur_id)
    : null;
$chasse_ids = array();

if ($chasses_query instanceof WP_Query) {
    $chasse_ids = array_map('intval', $chasses_query->posts);
} elseif (is_array($chasses_query)) {
    $chasse_ids = array_map('intval', $chasses_query);
}

if (function_exists('chasse_est_visible_pour_utilisateur')) {
    $chasse_ids = array_values(array_filter(
        $chasse_ids,
        static function ($chasse_id) use ($user_id) {
            return chasse_est_visible_pour_utilisateur((int) $chasse_id, $user_id);
        }
    ));
}

$can_create_hunt = function_exists('utilisateur_peut_ajouter_chasse')
    ? utilisateur_peut_ajouter_chasse($organisateur_id)
    : false;
?>
<section class="myaccount-organisation-hunts">
    <div class="myaccount-section-header">
        <h2 class="myaccount-section-title"><?php esc_html_e('Chasses de votre organisation', 'chassesautresor-com'); ?></h2>
        <?php if ($can_create_hunt) : ?>
        <a class="myaccount-section-action bouton-cta" href="<?php echo esc_url(home_url('/creer-chasse/')); ?>">
            <?php esc_html_e('Créer une chasse', 'chassesautresor-com'); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php if (!empty($chasse_ids)) : ?>
        <?php
        get_template_part(
            'template-parts/chasse/boucle-chasses',
            null,
            array(
                'show_header' => false,
                'mode'        => 'carte',
                'grid_class'  => 'cards-grid myaccount-organisation-hunts-grid',
                'chasse_ids'  => $chasse_ids,
            )
        );
        ?>
    <?php else : ?>
        <p class="myaccount-placeholder"><?php esc_html_e('Aucune chasse n’est liée à votre organisation pour le moment.', 'chassesautresor-com'); ?></p>
    <?php endif; ?>
</section>
