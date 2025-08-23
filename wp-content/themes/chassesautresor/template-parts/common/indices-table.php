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
$objet_titre = get_the_title($objet_id);

if (empty($indices)) {
    $titre = get_the_title($objet_id);
    if ($titre === TITRE_DEFAUT_ENIGME) {
        $titre = __('en création', 'chassesautresor-com');
    } elseif ($titre === TITRE_DEFAUT_CHASSE) {
        $titre = __('Nouvelle chasse', 'chassesautresor-com');
    }
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
      <th><?= esc_html__('Indice pour', 'chassesautresor-com'); ?></th>
      <th><?= esc_html__('Titre contenu', 'chassesautresor-com'); ?></th>
      <th><?= esc_html__('Statut', 'chassesautresor-com'); ?></th>
      <th><?= esc_html__('Action', 'chassesautresor-com'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($indices as $indice) :
        $date    = mysql2date('d/m/y', $indice->post_date);
        $img_id     = get_field('indice_image', $indice->ID);
        $img_html   = $img_id ? wp_get_attachment_image($img_id, 'thumbnail') : '';
        $contenu    = wp_strip_all_tags(get_field('indice_contenu', $indice->ID) ?: '');
        $dispo      = get_field('indice_disponibilite', $indice->ID) ?: 'immediate';
        $date_dispo = get_field('indice_date_disponibilite', $indice->ID) ?: '';

        $etat    = get_field('indice_cache_etat_systeme', $indice->ID) ?: '';
        $etat_class = 'etiquette-error';
        if ($etat === 'accessible') {
            $etat_class = 'etiquette-success';
        } elseif ($etat === 'programme' || $etat === 'programmé') {
            $etat_class = 'etiquette-pending';
        }

        $cible_type  = get_field('indice_cible_type', $indice->ID) === 'enigme' ? 'enigme' : 'chasse';
        $cible_label = $cible_type === 'enigme'
            ? __('Énigme', 'chassesautresor-com')
            : __('Chasse', 'chassesautresor-com');
        $linked_html = '';
        $linked      = $cible_type === 'enigme'
            ? get_field('indice_enigme_linked', $indice->ID)
            : get_field('indice_chasse_linked', $indice->ID);
        if ($linked) {
            if (is_array($linked)) {
                $first     = $linked[0] ?? null;
                $linked_id = is_array($first) ? ($first['ID'] ?? 0) : $first;
            } else {
                $linked_id = $linked;
            }
            if (!empty($linked_id)) {
                $linked_title = get_the_title($linked_id);
                $linked_html  = '<a href="' . esc_url(get_permalink($linked_id)) . '">' .
                    esc_html($linked_title) . '</a>';
            }
        }
    ?>
    <tr>
      <td><?= esc_html($date); ?></td>
      <td><?= $img_html; ?></td>
      <td><a href="<?= esc_url(get_permalink($indice)); ?>"><?= esc_html(get_the_title($indice)); ?></a></td>
      <?php echo cta_render_proposition_cell($contenu); ?>
      <td><span class="etiquette"><?= esc_html($cible_label); ?></span></td>
      <td><?= $linked_html; ?></td>
      <td><span class="etiquette <?= esc_attr($etat_class); ?>"><?= esc_html($etat); ?></span></td>
      <td class="indice-actions">
        <button
          type="button"
          class="badge-action edit"
          data-objet-type="<?= esc_attr($objet_type); ?>"
          data-objet-id="<?= esc_attr($objet_id); ?>"
          data-objet-titre="<?= esc_attr($objet_titre); ?>"
          data-indice-id="<?= esc_attr($indice->ID); ?>"
          data-indice-image="<?= esc_attr($img_id); ?>"
          data-indice-contenu="<?= esc_attr($contenu); ?>"
          data-indice-disponibilite="<?= esc_attr($dispo); ?>"
          data-indice-date="<?= esc_attr($date_dispo); ?>"
          title="<?= esc_attr__('Éditer', 'chassesautresor-com'); ?>"
        >
          <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
        </button>
        <button
          type="button"
          class="badge-action delete"
          data-indice-id="<?= esc_attr($indice->ID); ?>"
          data-confirm="<?= esc_attr__('Supprimer cet indice ?', 'chassesautresor-com'); ?>"
          title="<?= esc_attr__('Supprimer', 'chassesautresor-com'); ?>"
        >
          <i class="fa-solid fa-trash" aria-hidden="true"></i>
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php echo cta_render_pager($page, $pages, 'indices-pager'); ?>
