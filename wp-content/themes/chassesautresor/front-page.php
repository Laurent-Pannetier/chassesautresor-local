<?php
/**
 * Homepage displaying all valid hunts.
 */

defined('ABSPATH') || exit;

$points_history = '';
if (is_user_logged_in() && function_exists('render_points_history_table')) {
    $points_history = render_points_history_table((int) get_current_user_id());
}

get_header();

$query = new WP_Query([
    'post_type'      => 'chasse',
    'post_status'    => 'publish',
    'meta_query'     => [
        [
            'key'   => 'chasse_cache_statut_validation',
            'value' => 'valide',
        ],
    ],
    'fields'         => 'ids',
    'posts_per_page' => -1,
]);

$chasse_ids = $query->posts;

$user_id = get_current_user_id();
$filtered_chasse_ids = $chasse_ids;

if (function_exists('chasse_est_visible_pour_utilisateur')) {
    $filtered_chasse_ids = array_values(array_filter(
        $filtered_chasse_ids,
        static function ($chasse_id) use ($user_id) {
            return chasse_est_visible_pour_utilisateur((int) $chasse_id, $user_id);
        }
    ));
}

$default_status_filter = 'tous';
$default_cost_filters  = ['gratuit', 'points'];
$initial_results_count = count($filtered_chasse_ids);

$status_options = [
    'tous'     => __('Tous les statuts', 'chassesautresor-com'),
    'en_cours' => __('En cours', 'chassesautresor-com'),
    'a_venir'  => __('À venir', 'chassesautresor-com'),
    'termine'  => __('Terminées', 'chassesautresor-com'),
];

$results_label = sprintf(
    _n('%d résultat', '%d résultats', $initial_results_count, 'chassesautresor-com'),
    $initial_results_count
);

ob_start();
?>
<form class="home-hunts__filters" data-home-hunts-filters aria-label="<?php echo esc_attr(__('Filtrer les chasses', 'chassesautresor-com')); ?>">
    <div class="home-hunts__filters-group home-hunts__filters-group--status">
        <label for="home-hunts-status"><?php echo esc_html(__('Statut', 'chassesautresor-com')); ?></label>
        <select
            id="home-hunts-status"
            name="home-hunts-status"
            data-home-hunts-select="statut"
            data-default-value="<?php echo esc_attr($default_status_filter); ?>"
        >
            <?php foreach ($status_options as $status_value => $status_label) : ?>
                <option value="<?php echo esc_attr($status_value); ?>"<?php echo selected($default_status_filter, $status_value, false); ?>>
                    <?php echo esc_html($status_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <fieldset class="home-hunts__filters-group home-hunts__filters-group--cost" data-home-hunts-cost-group>
        <legend><?php echo esc_html(__('Coût', 'chassesautresor-com')); ?></legend>
        <div class="home-hunts__filters-checkbox">
            <input
                type="checkbox"
                id="home-hunts-cost-free"
                name="home-hunts-cost[]"
                value="gratuit"
                data-home-hunts-checkbox="gratuit"
                data-default-checked="true"<?php echo checked(in_array('gratuit', $default_cost_filters, true), true, false); ?>
            />
            <label for="home-hunts-cost-free"><?php echo esc_html(__('Gratuit', 'chassesautresor-com')); ?></label>
        </div>
        <div class="home-hunts__filters-checkbox">
            <input
                type="checkbox"
                id="home-hunts-cost-points"
                name="home-hunts-cost[]"
                value="points"
                data-home-hunts-checkbox="points"
                data-default-checked="true"<?php echo checked(in_array('points', $default_cost_filters, true), true, false); ?>
            />
            <label for="home-hunts-cost-points"><?php echo esc_html(__('Points', 'chassesautresor-com')); ?></label>
        </div>
    </fieldset>
    <div class="home-hunts__filters-actions">
        <button type="reset" class="home-hunts__filters-reset" data-home-hunts-reset><?php echo esc_html(__('Réinitialiser', 'chassesautresor-com')); ?></button>
        <span class="home-hunts__filters-count" data-home-hunts-count data-default-count="<?php echo esc_attr($initial_results_count); ?>"><?php echo esc_html($results_label); ?></span>
    </div>
</form>
<?php
$before_items_markup = ob_get_clean();

$after_items_markup = '<div class="home-hunts__feedback" data-home-hunts-feedback aria-live="polite"></div>';

$chasse_ids = $filtered_chasse_ids;
?>

<div id="primary" class="content-area">
    <main id="home-page">
        <section class="chasses">
            <div class="conteneur">
                <div class="liste-chasses" data-home-hunts="true">
                    <?php
                    get_template_part('template-parts/organisateur/organisateur-partial-boucle-chasses', null, [
                        'chasse_ids' => $chasse_ids,
                        'show_header' => false,
                        'grid_class' => 'organisateur-chasses-grid',
                        'before_items' => $before_items_markup,
                        'after_items' => $after_items_markup,
                    ]);
                    ?>
                </div>
            </div>
        </section>
        <?php if ($points_history) : ?>
            <section class="points-history">
                <div class="conteneur">
                    <?php echo $points_history; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>
