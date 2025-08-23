<?php
/**
 * Display a table of indices for a hunt or riddle.
 *
 * Variables:
 * - $indices    (array WP_Post)
 * - $page       (int)
 * - $pages      (int)
 * - $objet_type ('chasse'|'enigme')
 * - $objet_id   (int)
 */

defined('ABSPATH') || exit;

$args       = $args ?? [];
$indices    = $args['indices'] ?? $indices ?? [];
$page       = $args['page'] ?? $page ?? 1;
$pages      = $args['pages'] ?? $pages ?? 1;
$objet_type = $args['objet_type'] ?? $objet_type ?? 'chasse';
$objet_id   = $args['objet_id'] ?? $objet_id ?? 0;

if (empty($indices)) {
    $titre = get_the_title($objet_id);
    echo '<p>' . esc_html(sprintf(__('Vous n\'avez publié aucun indice attaché à %s', 'chassesautresor-com'), $titre)) . '</p>';
    return;
}
?>
<table class="stats-table indices-table">
  <thead>
    <tr>
      <th><?= esc_html__('Date', 'chassesautresor-com'); ?></th>
      <th><?= esc_html__('Image', 'chassesautresor-com'); ?></th>
      <th><?= esc_html__('Titre', 'chassesautresor-com'); ?></th>
      <th><?= esc_html__('Texte', 'chassesautresor-com'); ?></th>
      <?php if ($objet_type === 'chasse') : ?>
      <th><?= esc_html__('Énigme', 'chassesautresor-com'); ?></th>
      <?php endif; ?>
      <th><?= esc_html__('Statut', 'chassesautresor-com'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($indices as $indice) :
        $date    = mysql2date('d/m/y', $indice->post_date);
        $img_id  = get_field('indice_image', $indice->ID);
        $img_html = $img_id ? wp_get_attachment_image($img_id, 'thumbnail') : '';
        $contenu = wp_strip_all_tags(get_field('indice_contenu', $indice->ID) ?: '');
        $etat    = get_field('indice_cache_etat_systeme', $indice->ID) ?: '';
        $etat_class = 'etiquette-error';
        if ($etat === 'accessible') {
            $etat_class = 'etiquette-success';
        } elseif ($etat === 'programme' || $etat === 'programmé') {
            $etat_class = 'etiquette-pending';
        }
        $enigme_titre = '';
        if ($objet_type === 'chasse') {
            $linked = get_field('indice_enigme_linked', $indice->ID);
            if ($linked) {
                if (is_array($linked)) {
                    $first = $linked[0] ?? null;
                    $enigme_id = is_array($first) ? ($first['ID'] ?? 0) : $first;
                } else {
                    $enigme_id = $linked;
                }
                if (!empty($enigme_id)) {
                    $enigme_titre = get_the_title($enigme_id);
                }
            }
        }
    ?>
    <tr>
      <td><?= esc_html($date); ?></td>
      <td><?= $img_html; ?></td>
      <td><a href="<?= esc_url(get_permalink($indice)); ?>"><?= esc_html(get_the_title($indice)); ?></a></td>
      <?php echo cta_render_proposition_cell($contenu); ?>
      <?php if ($objet_type === 'chasse') : ?>
      <td><?= esc_html($enigme_titre); ?></td>
      <?php endif; ?>
      <td><span class="etiquette <?= esc_attr($etat_class); ?>"><?= esc_html($etat); ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php echo cta_render_pager($page, $pages, 'indices-pager'); ?>
