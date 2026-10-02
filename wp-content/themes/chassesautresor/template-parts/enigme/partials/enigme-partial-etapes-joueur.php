<?php

defined('ABSPATH') || exit;

$riddleId = isset($args['riddle_id']) ? (int) $args['riddle_id'] : 0;
$state = isset($args['state']) && is_array($args['state']) ? $args['state'] : [];
$visibleIds = array_map('intval', $state['visible_step_ids'] ?? []);
$completedIds = array_map('intval', $state['completed_step_ids'] ?? []);
$currentId = (int) ($state['current_step_id'] ?? 0);
if ($riddleId <= 0 || $visibleIds === []) {
    return;
}
?>
<section class="riddle-steps-player" aria-label="<?= esc_attr__('Étapes intermédiaires', 'chassesautresor-com'); ?>">
  <?php foreach ($visibleIds as $stepId) : ?>
    <?php
    $completed = in_array($stepId, $completedIds, true);
    $imageId = (int) get_field('etape_image', $stepId);
    $content = (string) get_field('etape_contenu', $stepId);
    ?>
    <article
      class="riddle-player-step<?= $completed ? ' is-completed' : ' is-current'; ?>"
      data-player-step-id="<?= esc_attr($stepId); ?>"
    >
      <?php if ($imageId > 0) : ?>
        <?= wp_get_attachment_image($imageId, 'large', false, ['class' => 'riddle-player-step__image']); ?>
      <?php endif; ?>
      <?php if (trim($content) !== '') : ?>
        <div class="riddle-player-step__content"><?= wp_kses_post($content); ?></div>
      <?php endif; ?>
      <?php if (!$completed && $stepId === $currentId) : ?>
        <?php
        $configuration = (new ChassesAuTresor\Core\Progress\AnswerWidgetConfigurationService())->forStep($stepId);
        $maxFailures = (int) get_field('enigme_tentative_max', $riddleId);
        $usedFailures = 0;
        if ($configuration['type'] === 'text') {
            global $wpdb;
            $usedFailures = ChassesAuTresor\Core\Support\CoreServiceFactory::riddleAttempts($wpdb)
                ->countFailuresTodayForUser((int) get_current_user_id(), $riddleId);
        }
        $widgetView = (new ChassesAuTresor\Core\Progress\AnswerWidgetPlayerViewService())->build(
            $configuration,
            $maxFailures,
            $usedFailures
        );
        ?>
        <form
          class="<?= esc_attr($widgetView['form_class']); ?>"
          data-widget-action="<?= esc_attr($widgetView['action']); ?>"
          data-max-failures="<?= esc_attr($maxFailures); ?>"
        >
          <input type="hidden" name="enigme_id" value="<?= esc_attr($riddleId); ?>">
          <input type="hidden" name="etape_id" value="<?= esc_attr($stepId); ?>">
          <input
            type="hidden"
            name="nonce"
            value="<?= esc_attr(wp_create_nonce($widgetView['nonce_action'])); ?>"
          >
          <?php if ($widgetView['type'] === 'text') : ?>
            <?php if ($widgetView['limit_reached']) : ?>
              <p class="message-limite"><?= esc_html__('Limite quotidienne atteinte.', 'chassesautresor-com'); ?></p>
            <?php else : ?>
              <label for="riddle-step-answer-<?= esc_attr($stepId); ?>">
                <?= esc_html($widgetView['input_label']); ?>
              </label>
              <input
                id="riddle-step-answer-<?= esc_attr($stepId); ?>"
                type="text"
                name="<?= esc_attr($widgetView['input_name']); ?>"
                required
              >
              <button type="submit" class="bouton-cta bouton-cta--color">
                <?= esc_html($widgetView['button_label']); ?>
              </button>
            <?php endif; ?>
          <?php else : ?>
            <button type="submit" class="bouton-cta bouton-cta--color">
              <?= esc_html($widgetView['button_label']); ?>
            </button>
          <?php endif; ?>
          <p class="riddle-step-click-form__feedback" role="status" aria-live="polite"></p>
        </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
