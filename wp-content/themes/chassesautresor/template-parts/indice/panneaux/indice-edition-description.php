<?php
/**
 * Panneau latéral pour éditer le texte d'un indice (champ ACF wysiwyg)
 * Requiert : $args['indice_id']
 */

defined('ABSPATH') || exit;

$indice_id = $args['indice_id'] ?? null;
if (!$indice_id || get_post_type($indice_id) !== 'indice') {
    return;
}
?>

<div id="panneau-description-indice" class="panneau-lateral-liens panneau-lateral-large" aria-hidden="true" role="dialog">
    <div class="panneau-lateral__contenu">
        <header class="panneau-lateral__header">
            <h2><?php echo esc_html__( 'Modifier le texte de l’indice', 'chassesautresor-com' ); ?></h2>
            <button type="button" class="panneau-fermer" aria-label="<?php echo esc_attr__( 'Fermer le panneau', 'chassesautresor-com' ); ?>">✖</button>
        </header>
        <?php
        acf_form([
            'post_id'            => $indice_id,
            'fields'             => ['indice_contenu'],
            'form'               => true,
            'submit_value'       => __( '💾 Enregistrer', 'chassesautresor-com' ),
            'html_submit_button' => '<div class="panneau-lateral__actions"><button type="submit" class="bouton-enregistrer-description bouton-enregistrer-liens">%s</button></div>',
            'html_before_fields' => '<div class="champ-wrapper">',
            'html_after_fields'  => '</div>',
            'return'             => add_query_arg('panneau', 'description-indice', get_permalink()),
            'updated_message'    => __( 'Texte de l’indice mis à jour.', 'chassesautresor-com' ),
        ]);
        ?>
    </div>
</div>
