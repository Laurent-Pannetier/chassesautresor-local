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

$search_term  = ca_get_search_term('home-hunts');
$home_filters = ca_home_filter_chasse_ids([
    'search' => $search_term,
]);
$filters_nonce = wp_create_nonce('ca-filter-chasses');

$chasse_ids             = $home_filters['ids'];
$normalized_filters     = $home_filters['filters_normalises'] ?? [];
$default_status_filter  = is_string($normalized_filters['statut'] ?? null)
    ? $normalized_filters['statut']
    : 'tous';
$default_cost_filters   = is_array($normalized_filters['cout'] ?? null)
    ? $normalized_filters['cout']
    : ['gratuit', 'points'];
$initial_results_count  = (int) ($home_filters['total'] ?? count($chasse_ids));

$available_filters = [];
if (isset($home_filters['available_filters']) && is_array($home_filters['available_filters'])) {
    $available_filters = $home_filters['available_filters'];
}

$available_status_counts = [];
if (isset($available_filters['statut']) && is_array($available_filters['statut'])) {
    foreach ($available_filters['statut'] as $status_value => $count) {
        $available_status_counts[$status_value] = (int) $count;
    }
}

$available_cost_counts = [];
if (isset($available_filters['cout']) && is_array($available_filters['cout'])) {
    foreach ($available_filters['cout'] as $cost_value => $count) {
        $available_cost_counts[$cost_value] = (int) $count;
    }
}

if ('tous' !== $default_status_filter) {
    $status_available_count = $available_status_counts[$default_status_filter] ?? 0;
    if ($status_available_count <= 0) {
        $default_status_filter = 'tous';
    }
}

$available_cost_values = [];
foreach ($available_cost_counts as $cost_value => $count) {
    if ($count > 0) {
        $available_cost_values[] = $cost_value;
    }
}

$available_cost_values = array_values(array_unique($available_cost_values));

$default_cost_filters = array_values(array_intersect($default_cost_filters, $available_cost_values));

if (empty($default_cost_filters) && !empty($available_cost_values)) {
    $default_cost_filters = $available_cost_values;
}

$status_options = [
    'tous'     => __('Tous les statuts', 'chassesautresor-com'),
    'en_cours' => __('En cours', 'chassesautresor-com'),
    'a_venir'  => __('À venir', 'chassesautresor-com'),
    'termine'  => __('Terminées', 'chassesautresor-com'),
];

$cost_options = [
    'gratuit' => [
        'label' => __('Gratuit', 'chassesautresor-com'),
        'id'    => 'home-hunts-cost-free',
    ],
    'points'  => [
        'label' => __('Points', 'chassesautresor-com'),
        'id'    => 'home-hunts-cost-points',
    ],
];

$results_label = sprintf(
    _n('%d résultat', '%d résultats', $initial_results_count, 'chassesautresor-com'),
    $initial_results_count
);

ob_start();
?>
<div class="home-hunts__filters">
    <form
        class="home-hunts__filters-form"
        data-home-hunts-filters
        aria-label="<?php echo esc_attr(__('Filtrer les chasses', 'chassesautresor-com')); ?>"
    >
        <div class="home-hunts__filters-group home-hunts__filters-group--status">
            <label for="home-hunts-status"><?php echo esc_html(__('Statut', 'chassesautresor-com')); ?></label>
            <select
                id="home-hunts-status"
                name="home-hunts-status"
                data-home-hunts-select="statut"
                data-default-value="<?php echo esc_attr($default_status_filter); ?>"
            >
                <?php foreach ($status_options as $status_value => $status_label) : ?>
                    <?php
                    $status_count = $available_status_counts[$status_value] ?? 0;
                    $status_is_available = ('tous' === $status_value) || ($status_count > 0);
                    ?>
                    <option
                        value="<?php echo esc_attr($status_value); ?>"
                        data-home-hunts-status-option="<?php echo esc_attr($status_value); ?>"
                        <?php echo selected($default_status_filter, $status_value, false); ?>
                        <?php if (!$status_is_available) : ?>hidden disabled<?php endif; ?>
                    >
                        <?php echo esc_html($status_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <fieldset class="home-hunts__filters-group home-hunts__filters-group--cost" data-home-hunts-cost-group>
            <legend><?php echo esc_html(__('Coût', 'chassesautresor-com')); ?></legend>
            <?php foreach ($cost_options as $cost_value => $cost_option) : ?>
                <?php
                $is_cost_available = in_array($cost_value, $available_cost_values, true);
                $is_cost_checked  = in_array($cost_value, $default_cost_filters, true);
                $cost_input_id    = $cost_option['id'] ?? ('home-hunts-cost-' . $cost_value);
                ?>
                <div
                    class="home-hunts__filters-checkbox"
                    data-home-hunts-cost-option="<?php echo esc_attr($cost_value); ?>"<?php echo $is_cost_available ? '' : ' hidden'; ?>
                >
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($cost_input_id); ?>"
                        name="home-hunts-cost[]"
                        value="<?php echo esc_attr($cost_value); ?>"
                        data-home-hunts-checkbox="<?php echo esc_attr($cost_value); ?>"
                        data-default-checked="<?php echo $is_cost_checked ? 'true' : 'false'; ?>"
                        <?php echo checked($is_cost_checked, true, false); ?>
                        <?php echo disabled($is_cost_available, false, false); ?>
                    />
                    <label for="<?php echo esc_attr($cost_input_id); ?>"><?php echo esc_html($cost_option['label'] ?? ''); ?></label>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <div class="home-hunts__filters-actions">
            <button type="reset" class="home-hunts__filters-reset" data-home-hunts-reset><?php echo esc_html(__('Réinitialiser', 'chassesautresor-com')); ?></button>
            <span class="home-hunts__filters-count" data-home-hunts-count data-default-count="<?php echo esc_attr($initial_results_count); ?>"><?php echo esc_html($results_label); ?></span>
        </div>
    </form>
    <div class="home-hunts__filters-search">
        <?php
        echo cta_render_search_form('home-hunts', [
            'class'             => 'table-search--inline table-search--compact',
            'label'             => '',
            'placeholder'       => __('Rechercher une chasse', 'chassesautresor-com'),
            'submit_icon'       => 'search',
            'show_reset_button' => true,
            'data_attributes'   => [
                'home-hunts-search' => '1',
            ],
        ]);
        ?>
    </div>
</div>
<?php
$before_items_markup = ob_get_clean();

$initial_feedback_message = '';
if ($initial_results_count <= 0 && !empty($home_filters['message'])) {
    $initial_feedback_message = esc_html((string) $home_filters['message']);
}

$after_items_markup = sprintf(
    '<div class="home-hunts__feedback" data-home-hunts-feedback aria-live="polite">%s</div>',
    $initial_feedback_message
);
?>

<div id="primary" class="content-area">
    <main id="home-page">
        <section class="chasses">
            <div class="conteneur">
                <div
                    class="liste-chasses"
                    data-home-hunts="true"
                    data-nonce="<?php echo esc_attr($filters_nonce); ?>"
                >
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
