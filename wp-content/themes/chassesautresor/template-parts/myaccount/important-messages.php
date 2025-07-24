<?php
/**
 * Affiche les messages importants dans l'espace Mon Compte.
 */
defined('ABSPATH') || exit;
$messages = function_exists('cta_get_important_messages') ? cta_get_important_messages() : [];
if (!empty($messages)) : ?>
<div class="important-messages">
  <?php foreach ($messages as $message) : ?>
    <div class="important-message"><?php echo esc_html($message); ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
