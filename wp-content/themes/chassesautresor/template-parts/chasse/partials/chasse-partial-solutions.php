<?php
/**
 * Displays solutions add card for a hunt or riddle.
 *
 * Variables:
 * - $objet_id   (int)
 * - $objet_type ('chasse'|'enigme')
 */

defined('ABSPATH') || exit;

$args = $args ?? [];
$objet_id = $args['objet_id'] ?? $chasse_id ?? 0;
$objet_type = $args['objet_type'] ?? 'chasse';
$default_enigme = $args['default_enigme'] ?? null;

$peut_ajouter = solution_action_autorisee('create', $objet_type, $objet_id);
$enigmes_disponibles = $objet_type === 'chasse' ? recuperer_enigmes_pour_chasse($objet_id) : [];
$has_enigmes = !empty($enigmes_disponibles);
?>
<div class='dashboard-card carte-orgy champ-<?= esc_attr($objet_type); ?> champ-solutions<?= $peut_ajouter ? '' : ' disabled'; ?>'
>
    <i class='fa-solid fa-lightbulb icone-defaut' aria-hidden='true'></i>
    <h3><?= esc_html__('Ajouter une solution', 'chassesautresor-com'); ?></h3>
<?php if ($peut_ajouter) : ?>
    <div class='stat-value'>
        <button
            type='button'
            class='bouton-cta cta-solution-pour'
        >
            <?= esc_html__('Pour…', 'chassesautresor-com'); ?>
        </button>
        <div class='cta-solution-options'>
            <button
                type='button'
                class='bouton-cta cta-solution-chasse ajouter-solution'
                data-objet-type='chasse'
                data-objet-id='<?= esc_attr($objet_id); ?>'
            >
                <?= esc_html__('La chasse entière', 'chassesautresor-com'); ?>
            </button>
            <?php if ($has_enigmes) : ?>
                <button
                    type='button'
                    class='bouton-cta cta-solution-enigme ajouter-solution'
                    data-objet-type='enigme'
                    data-chasse-id='<?= esc_attr($objet_id); ?>'
                    <?php if ($default_enigme) : ?>
                        data-default-enigme='<?= esc_attr($default_enigme); ?>'
                    <?php endif; ?>
                >
                    <?= esc_html__('Une énigme de la chasse', 'chassesautresor-com'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
<?php else : ?>
    <span class='stat-value'>
        <?= esc_html__('Ajouter', 'chassesautresor-com'); ?>
    </span>
<?php endif; ?>
</div>
