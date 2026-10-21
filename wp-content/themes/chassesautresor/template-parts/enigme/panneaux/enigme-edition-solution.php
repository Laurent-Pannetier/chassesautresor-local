<?php
defined('ABSPATH') || exit;

$enigme_id = $args['enigme_id'] ?? null;
if (!$enigme_id || get_post_type($enigme_id) !== 'enigme') return;
?>

<div id="panneau-solution-enigme" class="panneau-lateral-liens panneau-lateral-large" aria-hidden="true" role="dialog">
  <div class="panneau-lateral__contenu">

    <header class="panneau-lateral__header">
      <h2>Rédiger la solution de cette énigme</h2>
      <button type="button" class="panneau-fermer" aria-label="Fermer le panneau">✖</button>
    </header>

    <div class="champ-wrapper">
      <?php
      $mode_field = acf_get_field('enigme_solution_mode');
      $mode_key   = $mode_field['key'] ?? '';

      $return_url = function_exists('cta_shell_edition_url')
        ? add_query_arg('maj', 'solution', cta_shell_edition_url('enigme', (int) $enigme_id, 'parametres'))
            . '#panneau-solution-enigme'
        : add_query_arg(
            [
              'maj'     => 'solution',
              'edition' => 'open',
              'tab'     => 'solution',
            ],
            get_permalink($enigme_id)
        );

      acf_form([
        'post_id'         => $enigme_id,
        'form'            => true,
        'field_groups'    => false,
        'fields'          => [
          'enigme_solution_explication',
        ],
        'submit_value'    => '💾 Enregistrer la solution',
        'return'          => $return_url,
        'uploader'        => 'basic',
        'label_placement' => 'top',
        'html_after_fields' => $mode_key ? '<input type="hidden" name="acf[' . esc_attr($mode_key) . ']" value="texte" />' : '',
      ]);
      ?>
    </div>

  </div>
</div>