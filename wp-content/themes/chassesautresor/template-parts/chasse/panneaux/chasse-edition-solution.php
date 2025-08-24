<?php
defined('ABSPATH') || exit;

$chasse_id = $args['chasse_id'] ?? null;
if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
    return;
}
?>

<div id="panneau-solution-chasse" class="panneau-lateral-liens panneau-lateral-large" aria-hidden="true" role="dialog">
    <div class="panneau-lateral__contenu">

        <header class="panneau-lateral__header">
            <h2><?= esc_html__('Rédiger la solution de cette chasse', 'chassesautresor-com'); ?></h2>
            <button type="button" class="panneau-fermer" aria-label="<?= esc_attr__('Fermer le panneau', 'chassesautresor-com'); ?>">✖</button>
        </header>

        <div class="champ-wrapper">
            <?php
            $mode_field = acf_get_field('chasse_solution_mode');
            $mode_key   = $mode_field['key'] ?? '';

            $return_url = add_query_arg(
                [
                    'maj'     => 'solution',
                    'edition' => 'open',
                    'tab'     => 'animation',
                ],
                get_permalink($chasse_id)
            );

            acf_form([
                'post_id'          => $chasse_id,
                'form'             => true,
                'field_groups'     => false,
                'fields'           => [
                    'chasse_solution_explication',
                ],
                'submit_value'     => '💾 ' . esc_html__('Enregistrer la solution', 'chassesautresor-com'),
                'return'           => $return_url,
                'uploader'         => 'basic',
                'label_placement'  => 'top',
                'html_after_fields' => $mode_key ? '<input type="hidden" name="acf[' . esc_attr($mode_key) . ']" value="texte" />' : '',
            ]);
            ?>
        </div>

    </div>
</div>

