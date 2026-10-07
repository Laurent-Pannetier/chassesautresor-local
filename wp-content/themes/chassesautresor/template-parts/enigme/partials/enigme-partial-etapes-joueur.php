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
<section
  class="riddle-steps-player"
  aria-label="<?= esc_attr__('Étapes intermédiaires', 'chassesautresor-com'); ?>"
>
  <?php foreach ($visibleIds as $stepId) : ?>
    <?php
    $completed = in_array($stepId, $completedIds, true);
    $imageId = (int) get_field('etape_image', $stepId);
    $content = (string) get_field('etape_contenu', $stepId);
    $plainContent = html_entity_decode(wp_strip_all_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $hasTextContent = preg_replace('/[\s\x{00A0}]+/u', '', $plainContent) !== '';
    $hasEmbeddedContent = preg_match(
        '/<(?:img|picture|video|audio|iframe|canvas|svg)\b/i',
        $content
    ) === 1;
    if ($completed && $imageId <= 0 && !$hasTextContent && !$hasEmbeddedContent) {
        continue;
    }

    $hasImage = $imageId > 0;
    $hasText = $hasTextContent || $hasEmbeddedContent;
    $isWidgetOnly = !$completed && !$hasImage && !$hasText;
    $hotspot = (new ChassesAuTresor\Core\Content\RiddleStepHotspotService())->forStep($stepId);
    $useHotspot = !$completed && $stepId === $currentId && !empty($hotspot['active']);
    $stepClasses = 'riddle-player-step';
    $stepClasses .= $completed ? ' is-completed' : ' is-current';
    if ($hasImage) {
        $stepClasses .= ' has-image';
    }
    if ($hasText) {
        $stepClasses .= ' has-text';
    }
    if ($isWidgetOnly) {
        $stepClasses .= ' is-widget-only';
    }
    if ($useHotspot) {
        $stepClasses .= ' has-hotspot';
    }
    ?>
    <article
      class="<?= esc_attr($stepClasses); ?>"
      data-player-step-id="<?= esc_attr($stepId); ?>"
    >
      <?php if ($hasImage) : ?>
        <?php
        $imageSource = wp_get_attachment_image_src($imageId, 'large');
        $imageAlt = trim((string) get_post_meta($imageId, '_wp_attachment_image_alt', true));
        $imageUrl = function_exists('cta_voir_image_enigme_url')
            ? cta_voir_image_enigme_url($imageId, 'large')
            : add_query_arg(
                ['id' => $imageId, 'taille' => 'large'],
                site_url('/voir-image-enigme')
            );
        $fullImageUrl = function_exists('cta_voir_image_enigme_url')
            ? cta_voir_image_enigme_url($imageId, 'full')
            : add_query_arg(
                ['id' => $imageId, 'taille' => 'full'],
                site_url('/voir-image-enigme')
            );
        $hotspotLabel = (string) ($hotspot['label'] ?? __('Zone interactive', 'chassesautresor-com'));
        $zone = is_array($hotspot['zone'] ?? null) ? $hotspot['zone'] : null;
        $zoneRaw = (string) ($hotspot['zone_raw'] ?? '');
        ?>
        <?php if ($useHotspot && $zone !== null) : ?>
          <figure class="riddle-player-step__media is-hotspot">
            <div
              class="riddle-player-step__stage"
              data-riddle-hotspot-zone="<?= esc_attr($zoneRaw); ?>"
            >
              <img
                class="riddle-player-step__image"
                src="<?= esc_url($imageUrl); ?>"
                alt="<?= esc_attr($imageAlt); ?>"
                loading="lazy"
                <?php if (is_array($imageSource)) : ?>
                  width="<?= esc_attr((string) $imageSource[1]); ?>"
                  height="<?= esc_attr((string) $imageSource[2]); ?>"
                <?php endif; ?>
              >
              <button
                type="button"
                class="riddle-player-step__hotspot"
                style="<?= esc_attr(sprintf(
                    'left:%s%%;top:%s%%;width:%s%%;height:%s%%;',
                    $zone['x'],
                    $zone['y'],
                    $zone['w'],
                    $zone['h']
                )); ?>"
                data-riddle-open-widget
                aria-label="<?= esc_attr($hotspotLabel); ?>"
              ></button>
              <button
                type="button"
                class="enigme-media-zoom riddle-player-step__zoom-btn"
                data-enigme-lightbox-src="<?= esc_url($fullImageUrl); ?>"
                data-enigme-lightbox-alt="<?= esc_attr($imageAlt); ?>"
                aria-label="<?= esc_attr__('Agrandir l’image en taille originale', 'chassesautresor-com'); ?>"
              >
                <span class="enigme-media-zoom__hint" aria-hidden="true">
                  <?= esc_html__('Agrandir', 'chassesautresor-com'); ?>
                </span>
              </button>
            </div>
          </figure>
        <?php else : ?>
          <button
            type="button"
            class="enigme-media-zoom riddle-player-step__zoom"
            data-enigme-lightbox-src="<?= esc_url($fullImageUrl); ?>"
            data-enigme-lightbox-alt="<?= esc_attr($imageAlt); ?>"
            aria-label="<?= esc_attr__('Agrandir l’image en taille originale', 'chassesautresor-com'); ?>"
          >
            <img
              class="riddle-player-step__image"
              src="<?= esc_url($imageUrl); ?>"
              alt="<?= esc_attr($imageAlt); ?>"
              loading="lazy"
              <?php if (is_array($imageSource)) : ?>
                width="<?= esc_attr((string) $imageSource[1]); ?>"
                height="<?= esc_attr((string) $imageSource[2]); ?>"
              <?php endif; ?>
            >
            <span class="enigme-media-zoom__hint" aria-hidden="true">
              <?= esc_html__('Agrandir', 'chassesautresor-com'); ?>
            </span>
          </button>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (trim($content) !== '') : ?>
        <div class="riddle-player-step__content"><?= wp_kses_post($content); ?></div>
      <?php endif; ?>
      <?php if (!$completed && $stepId === $currentId) : ?>
        <?php
        $configuration = (new ChassesAuTresor\Core\Progress\AnswerWidgetConfigurationService())->forStep($stepId);
        $maxFailures = 0;
        $usedFailures = 0;
        global $wpdb;
        $retryState = ChassesAuTresor\Core\Support\CoreServiceFactory::riddleRetry($wpdb)->getState(
            (int) get_current_user_id(),
            $riddleId
        );
        $widgetView = (new ChassesAuTresor\Core\Progress\AnswerWidgetPlayerViewService())->build(
            $configuration,
            $maxFailures,
            $usedFailures
        );
        ?>
        <form
          class="<?= esc_attr($widgetView['form_class']); ?><?= $useHotspot ? ' is-hotspot-widget' : ''; ?>"
          data-widget-action="<?= esc_attr($widgetView['action']); ?>"
          data-retry-state="<?= esc_attr(wp_json_encode($retryState)); ?>"
          aria-busy="false"
          <?php if ($useHotspot) : ?>
            hidden
          <?php endif; ?>
        >
          <?php if ($useHotspot) : ?>
            <div class="riddle-widget-immersive__chrome">
              <button
                type="button"
                class="riddle-widget-immersive__close"
                data-riddle-close-widget
                aria-label="<?= esc_attr__('Fermer', 'chassesautresor-com'); ?>"
              >
                ×
              </button>
            </div>
          <?php endif; ?>
          <input type="hidden" name="enigme_id" value="<?= esc_attr($riddleId); ?>">
          <input type="hidden" name="etape_id" value="<?= esc_attr($stepId); ?>">
          <input
            type="hidden"
            name="nonce"
            value="<?= esc_attr(wp_create_nonce($widgetView['nonce_action'])); ?>"
          >
          <?php
          get_template_part(
              'template-parts/enigme/partials/enigme-partial-answer-widget-controls',
              null,
              [
                  'widget_view' => $widgetView,
                  'field_suffix' => (string) $stepId,
                  'submit_label' => (string) $widgetView['button_label'],
              ]
          );
          ?>
          <p class="riddle-step-click-form__feedback" role="status" aria-live="polite"></p>
        </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
