<?php
/**
 * Display a table of solutions for a hunt or riddle.
 *
 * Variables:
 * - $solutions (array WP_Post)
 * - $page
 * - $pages
 * - $objet_type ('chasse'|'enigme')
 * - $objet_id (int)
 */

defined('ABSPATH') || exit;

$args        = $args ?? [];
$solutions   = $args['solutions'] ?? $solutions ?? [];
$page        = $args['page'] ?? $page ?? 1;
$pages       = $args['pages'] ?? $pages ?? 1;
$objet_type  = $args['objet_type'] ?? $objet_type ?? 'enigme';
$objet_id    = $args['objet_id'] ?? $objet_id ?? 0;

if (empty($solutions)) {
    echo '<p>' . esc_html__('Aucune solution publiée', 'chassesautresor-com') . '</p>';
    return;
}
?>
<table class="stats-table solutions-table">
    <thead>
        <tr>
            <th><?= esc_html__('Date', 'chassesautresor-com'); ?></th>
            <th><?= esc_html__('Solution', 'chassesautresor-com'); ?></th>
            <th><?= esc_html__('Type', 'chassesautresor-com'); ?></th>
            <th><?= esc_html__('Solution pour', 'chassesautresor-com'); ?></th>
            <th class="solution-actions"><?= esc_html__('Action', 'chassesautresor-com'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($solutions as $solution) :
            $timestamp   = strtotime($solution->post_date);
            $date_value  = function_exists('wp_date') ? wp_date('d/m/y', $timestamp) : date('d/m/y', $timestamp);
            $time_value  = function_exists('wp_date') ? wp_date('H:i', $timestamp) : date('H:i', $timestamp);
            $fichier_id  = get_field('solution_fichier', $solution->ID);
            $fichier_url = $fichier_id ? wp_get_attachment_url($fichier_id) : '';
            $explication = wp_strip_all_tags(get_field('solution_explication', $solution->ID) ?: '');
            $dispo       = get_field('solution_disponibilite', $solution->ID) ?: 'fin_chasse';
            $delai       = (int) get_field('solution_decalage_jours', $solution->ID);
            $heure       = get_field('solution_heure_publication', $solution->ID) ?: '18:00';

            $cible_type  = get_field('solution_cible_type', $solution->ID) === 'chasse' ? 'chasse' : 'enigme';
            $cible_label = $cible_type === 'enigme'
                ? __('Énigme', 'chassesautresor-com')
                : __('Chasse', 'chassesautresor-com');
            $linked      = $cible_type === 'enigme'
                ? get_field('solution_enigme_linked', $solution->ID)
                : get_field('solution_chasse_linked', $solution->ID);
            $linked_id   = 0;
            if ($linked) {
                if (is_array($linked)) {
                    $first     = $linked[0] ?? null;
                    $linked_id = is_array($first) ? ($first['ID'] ?? 0) : $first;
                } else {
                    $linked_id = $linked;
                }
            }
            $linked_html = '';
            if ($linked_id) {
                $linked_title = get_the_title($linked_id);
                $linked_html  = '<a href="' . esc_url(get_permalink($linked_id)) . '">' . esc_html($linked_title) . '</a>';
            }

            $etat       = get_field('solution_cache_etat_systeme', $solution->ID) ?: '';
            $date_raw   = get_field('solution_date_disponibilite', $solution->ID) ?: '';
            $dt         = $date_raw ? convertir_en_datetime($date_raw) : null;
            $etat_class = 'etiquette-error';
            $etat_label = __($etat, 'chassesautresor-com');
            if ($etat === 'accessible') {
                $etat_class = 'etiquette-success';
            } elseif ($etat === 'programme' || $etat === 'programmé') {
                $etat_class = 'etiquette-pending';
                if ($dispo === 'fin_chasse') {
                    $etat_label = __('fin de chasse', 'chassesautresor-com');
                } elseif ($dispo === 'differee') {
                    $etat_label = __('différée', 'chassesautresor-com');
                } elseif ($dt instanceof DateTimeInterface) {
                    $format     = get_option('date_format') . ' ' . get_option('time_format');
                    $date_label = function_exists('wp_date')
                        ? wp_date($format, $dt->getTimestamp())
                        : date($format, $dt->getTimestamp());
                    $etat_label = sprintf(
                        /* translators: %s: scheduled date */
                        __('programmé le %s', 'chassesautresor-com'),
                        $date_label
                    );
                } else {
                    $etat_label = __('programmé', 'chassesautresor-com');
                }
            }

            $target_url = $linked_id ? get_permalink($linked_id) : get_permalink($solution);
        ?>
        <tr>
            <td>
                <div><?= esc_html($date_value); ?></div>
                <div><?= esc_html(sprintf(__('à %s', 'chassesautresor-com'), $time_value)); ?></div>
            </td>
            <td><a href="<?= esc_url($target_url); ?>"><?= esc_html(get_the_title($solution)); ?></a></td>
            <td>
                <?php if ($fichier_url) : ?>
                    <span class="etiquette"><?= esc_html__('PDF', 'chassesautresor-com'); ?></span>
                <?php endif; ?>
                <?php if ($explication !== '') : ?>
                    <span class="etiquette"><?= esc_html__('Texte', 'chassesautresor-com'); ?></span>
                <?php endif; ?>
                <?php if (!$fichier_url && $explication === '') : ?>-
                <?php endif; ?>
            </td>
            <td>
                <div><span class="etiquette"><?= esc_html($cible_label); ?></span></div>
                <div><?= $linked_html; ?></div>
            </td>
            <td class="solution-actions">
                <div><span class="etiquette <?= esc_attr($etat_class); ?>"><?= esc_html($etat_label); ?></span></div>
                <div class="solution-action-buttons">
                    <button type="button"
                        class="badge-action edit"
                        data-objet-type="<?= esc_attr($objet_type); ?>"
                        data-objet-id="<?= esc_attr($objet_id); ?>"
                        data-objet-titre="<?= esc_attr(get_the_title($objet_id)); ?>"
                        data-solution-id="<?= esc_attr($solution->ID); ?>"
                        data-solution-explication="<?= esc_attr($explication); ?>"
                        data-solution-fichier-id="<?= esc_attr($fichier_id); ?>"
                        data-solution-fichier-url="<?= esc_attr($fichier_url); ?>"
                        data-solution-disponibilite="<?= esc_attr($dispo); ?>"
                        data-solution-delai="<?= esc_attr($delai); ?>"
                        data-solution-heure="<?= esc_attr($heure); ?>"
                        title="<?= esc_attr__('Éditer', 'chassesautresor-com'); ?>">
                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    </button>
                    <button type="button"
                        class="badge-action delete"
                        data-solution-id="<?= esc_attr($solution->ID); ?>"
                        data-confirm="<?= esc_attr__('Supprimer cette solution ?', 'chassesautresor-com'); ?>"
                        title="<?= esc_attr__('Supprimer', 'chassesautresor-com'); ?>">
                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php echo cta_render_pager($page, $pages, 'solutions-pager'); ?>
