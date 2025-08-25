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
$has_solution_chasse = $objet_type === 'chasse' && solution_existe_pour_objet($objet_id, 'chasse');
$enigmes_disponibles = $objet_type === 'chasse' ? recuperer_enigmes_pour_chasse($objet_id) : [];
$enigmes_disponibles = array_filter(
    $enigmes_disponibles,
    static fn($e) => !solution_existe_pour_objet($e->ID, 'enigme')
);
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
                class='bouton-cta cta-solution-chasse ajouter-solution<?= $has_solution_chasse ? ' disabled' : ''; ?>'
                data-objet-type='chasse'
                data-objet-id='<?= esc_attr($objet_id); ?>'
                data-objet-titre='<?= esc_attr(get_the_title($objet_id)); ?>'
                <?= $has_solution_chasse ? 'disabled' : ''; ?>
            >
                <?= esc_html__('La chasse entière', 'chassesautresor-com'); ?>
            </button>
            <button
                type='button'
                class='bouton-cta cta-solution-enigme ajouter-solution<?= $has_enigmes ? '' : ' disabled'; ?>'
                data-objet-type='enigme'
                data-chasse-id='<?= esc_attr($objet_id); ?>'
                <?php if ($default_enigme) : ?>
                    data-default-enigme='<?= esc_attr($default_enigme); ?>'
                <?php endif; ?>
                <?= $has_enigmes ? '' : 'disabled'; ?>
            >
                <?= esc_html__('Une énigme de la chasse', 'chassesautresor-com'); ?>
            </button>
        </div>
    </div>
<?php else : ?>
    <span class='stat-value'>
        <?= esc_html__('Ajouter', 'chassesautresor-com'); ?>
    </span>
<?php endif; ?>
</div>
