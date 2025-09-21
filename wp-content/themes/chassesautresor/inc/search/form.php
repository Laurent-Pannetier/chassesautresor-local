<?php
/**
 * Search form helper.
 *
 * @package chassesautresor.com
 */

defined('ABSPATH') || exit;

/**
 * Renders the HTML markup for a table search form.
 *
 * @param string $key       Context identifier.
 * @param array  $overrides Optional overrides (label, placeholder, etc.).
 *
 * @return string
 */
function cta_render_search_form(string $key, array $overrides = []): string
{
    $context = ca_resolve_search_context($key);

    if (empty($context)) {
        return '';
    }

    $ui          = $context['ui'] ?? [];
    $capability  = $overrides['capability'] ?? ($ui['capability'] ?? '');
    $has_cap     = true;

    if ($capability && function_exists('current_user_can')) {
        $has_cap = current_user_can($capability);
    }

    if (!$has_cap) {
        return '';
    }

    $parameter = $context['parameter'] ?? sanitize_key($key);
    $form_id   = $overrides['id'] ?? sprintf('table-search-%s', $parameter);

    $defaults = [
        'class'              => 'table-search',
        'method'             => 'get',
        'action'             => '',
        'label'              => $ui['label'] ?? '',
        'placeholder'        => $ui['placeholder'] ?? '',
        'value'              => null,
        'hidden_fields'      => [],
        'nonce_action'       => $ui['nonce_action'] ?? '',
        'nonce_name'         => $ui['nonce_name'] ?? 'nonce',
        'submit_label'       => $ui['submit_label'] ?? esc_html__('Rechercher', 'chassesautresor-com'),
        'reset_label'        => $ui['reset_label'] ?? esc_html__('Réinitialiser', 'chassesautresor-com'),
        'show_reset_button'  => $ui['show_reset_button'] ?? false,
        'pagination_params'  => $context['pagination_params'] ?? [],
        'description'        => $ui['description'] ?? '',
        'data_attributes'    => [],
    ];

    $config = array_merge($defaults, $overrides);

    $method = strtolower((string) $config['method']);
    if (!in_array($method, ['get', 'post'], true)) {
        $method = 'get';
    }

    $form_classes = trim((string) $config['class']);
    $class_tokens = preg_split('/\s+/', $form_classes);
    $class_tokens = array_filter(
        is_array($class_tokens) ? $class_tokens : [],
        static function ($class_name): bool {
            return is_string($class_name) && '' !== $class_name;
        }
    );

    if (!in_array('table-search', $class_tokens, true)) {
        array_unshift($class_tokens, 'table-search');
    }

    $form_classes = implode(' ', array_unique($class_tokens));

    $search_value = $config['value'];
    if (null === $search_value) {
        $search_value = ca_get_search_term($key);
    }

    $hidden_fields = array_merge(
        is_array($context['hidden_fields'] ?? null) ? $context['hidden_fields'] : [],
        is_array($ui['hidden_fields'] ?? null) ? $ui['hidden_fields'] : [],
        is_array($config['hidden_fields']) ? $config['hidden_fields'] : []
    );

    if (!array_key_exists('section', $hidden_fields) && isset($_GET['section'])) {
        $hidden_fields['section'] = sanitize_text_field(wp_unslash($_GET['section']));
    }

    $hidden_fields = array_merge(
        ['search[context]' => $context['key'] ?? $key],
        $hidden_fields
    );

    $pagination_params = array_values(
        array_unique(
            array_filter(
                array_map('trim', (array) $config['pagination_params']),
                static function ($value): bool {
                    return is_string($value) && '' !== $value;
                }
            )
        )
    );

    $input_id    = sprintf('%s-input', $form_id);
    $input_name  = sprintf('search[%s]', $parameter);
    $placeholder = (string) $config['placeholder'];
    $label       = (string) $config['label'];
    $desc        = (string) $config['description'];
    $submit      = (string) $config['submit_label'];
    $reset_label = (string) $config['reset_label'];
    $show_reset  = (bool) $config['show_reset_button'];

    $form_attrs = sprintf(' method="%s"', esc_attr($method));

    if (!empty($config['action'])) {
        $form_attrs .= sprintf(' action="%s"', esc_url($config['action']));
    }

    $form_attrs .= sprintf(' class="%s"', esc_attr($form_classes));
    $form_attrs .= sprintf(' id="%s"', esc_attr($form_id));
    $form_attrs .= sprintf(' data-search-key="%s"', esc_attr($context['key'] ?? $key));
    $form_attrs .= sprintf(' data-search-parameter="%s"', esc_attr($input_name));

    $data_attributes = [];
    $used_data_keys  = [
        'search-key',
        'search-parameter',
    ];

    if ($show_reset) {
        $form_attrs     .= ' data-has-reset="1"';
        $used_data_keys[] = 'has-reset';
    }

    if (!empty($pagination_params)) {
        $form_attrs     .= sprintf(' data-reset-pagination="%s"', esc_attr(implode(',', $pagination_params)));
        $used_data_keys[] = 'reset-pagination';
    }

    if (is_array($config['data_attributes'])) {
        foreach ($config['data_attributes'] as $data_key => $data_value) {
            $sanitized = strtolower((string) $data_key);
            $sanitized = preg_replace('/[^a-z0-9_-]+/', '', $sanitized);

            if ('' === $sanitized) {
                continue;
            }

            if (in_array($sanitized, $used_data_keys, true)) {
                continue;
            }

            $data_attributes[$sanitized] = (string) $data_value;
        }
    }

    foreach ($data_attributes as $data_key => $data_value) {
        $form_attrs .= sprintf(' data-%s="%s"', esc_attr($data_key), esc_attr($data_value));
    }

    $form_attrs .= ' role="search"';

    $input_attrs = [
        'type'        => 'search',
        'id'          => $input_id,
        'name'        => $input_name,
        'value'       => (string) $search_value,
        'placeholder' => $placeholder,
        'class'       => 'table-search__field',
    ];

    if ('' === $label) {
        $input_attrs['aria-label'] = $placeholder !== ''
            ? $placeholder
            : esc_attr__('Rechercher', 'chassesautresor-com');
    }

    if ($desc !== '') {
        $description_id             = sprintf('%s-description', $form_id);
        $input_attrs['aria-describedby'] = $description_id;
    }

    $input_html = '';
    foreach ($input_attrs as $attr => $value) {
        if ('' === $value) {
            continue;
        }

        $input_html .= sprintf(' %s="%s"', esc_attr($attr), esc_attr($value));
    }

    ob_start();
    ?>
    <form<?php echo $form_attrs; ?>>
        <?php if ('' !== $label) : ?>
            <label class="table-search__label" for="<?php echo esc_attr($input_id); ?>">
                <?php echo esc_html($label); ?>
            </label>
        <?php endif; ?>

        <?php if ('' !== $desc) : ?>
            <p class="table-search__description" id="<?php echo esc_attr($description_id); ?>">
                <?php echo esc_html($desc); ?>
            </p>
        <?php endif; ?>

        <div class="table-search__controls">
            <input<?php echo $input_html; ?> />
            <button type="submit" class="table-search__submit">
                <span class="table-search__submit-text"><?php echo esc_html($submit); ?></span>
            </button>
            <?php if ($show_reset) : ?>
            <button type="button" class="table-search__reset" data-table-search-reset<?php echo '' === $search_value ? ' hidden' : ''; ?>>
                <span class="table-search__reset-text"><?php echo esc_html($reset_label); ?></span>
            </button>
            <?php endif; ?>
        </div>

        <?php
        if (!empty($config['nonce_action'])) {
            echo wp_nonce_field($config['nonce_action'], $config['nonce_name'], true, false); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        foreach ($hidden_fields as $name => $value) :
            if ('' === $name) {
                continue;
            }
            ?>
            <input type="hidden" name="<?php echo esc_attr((string) $name); ?>" value="<?php echo esc_attr((string) $value); ?>" />
        <?php endforeach; ?>
    </form>
    <?php

    return trim((string) ob_get_clean());
}
