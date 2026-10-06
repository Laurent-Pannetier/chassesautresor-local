<?php
/**
 * Experience-aware public site footer.
 *
 * @package chassesautresor
 *
 * @var array $args {
 *     @type array $footer Footer payload from cta_get_site_footer_data().
 * }
 */

defined('ABSPATH') || exit;

$footer = is_array($args['footer'] ?? null) ? $args['footer'] : [];
$mode = (string) ($footer['mode'] ?? 'single_hunt');
$brand = is_array($footer['brand'] ?? null) ? $footer['brand'] : [];
$columns = is_array($footer['columns'] ?? null) ? $footer['columns'] : [];
$legalLinks = is_array($footer['legal_links'] ?? null) ? $footer['legal_links'] : [];
$copyright = (string) ($footer['copyright'] ?? '');

$brandTitle = (string) ($brand['title'] ?? get_bloginfo('name'));
$brandTagline = (string) ($brand['tagline'] ?? '');
$brandHomeUrl = (string) ($brand['home_url'] ?? home_url('/'));
$brandBadge = (string) ($brand['badge'] ?? '');

$footerClasses = [
    'cat-site-footer',
    'cat-site-footer--' . sanitize_html_class($mode),
];
?>
<footer
    class="<?php echo esc_attr(implode(' ', $footerClasses)); ?>"
    data-experience-mode="<?php echo esc_attr($mode); ?>"
    role="contentinfo"
>
    <div class="cat-site-footer__inner conteneur">
        <div class="cat-site-footer__brand">
            <a class="cat-site-footer__brand-link" href="<?php echo esc_url($brandHomeUrl); ?>">
                <?php if ($brandBadge !== '') : ?>
                    <span class="cat-site-footer__badge"><?php echo esc_html($brandBadge); ?></span>
                <?php endif; ?>
                <span class="cat-site-footer__brand-title"><?php echo esc_html($brandTitle); ?></span>
            </a>
            <?php if ($brandTagline !== '') : ?>
                <p class="cat-site-footer__tagline"><?php echo esc_html($brandTagline); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($columns)) : ?>
            <nav
                class="cat-site-footer__nav"
                aria-label="<?php echo esc_attr__('Pied de page', 'chassesautresor-com'); ?>"
            >
                <?php foreach ($columns as $column) :
                    $title = (string) ($column['title'] ?? '');
                    $links = is_array($column['links'] ?? null) ? $column['links'] : [];
                    if ($title === '' || empty($links)) {
                        continue;
                    }
                    $headingId = 'cat-footer-' . sanitize_title($title);
                    ?>
                    <div class="cat-site-footer__column">
                        <h2 class="cat-site-footer__column-title" id="<?php echo esc_attr($headingId); ?>">
                            <?php echo esc_html($title); ?>
                        </h2>
                        <ul class="cat-site-footer__list" aria-labelledby="<?php echo esc_attr($headingId); ?>">
                            <?php foreach ($links as $link) :
                                $label = (string) ($link['label'] ?? '');
                                $url = (string) ($link['url'] ?? '');
                                if ($label === '' || $url === '') {
                                    continue;
                                }
                                $linkClass = trim('cat-site-footer__link ' . (string) ($link['class'] ?? ''));
                                ?>
                                <li class="cat-site-footer__item">
                                    <a class="<?php echo esc_attr($linkClass); ?>" href="<?php echo esc_url($url); ?>">
                                        <?php echo esc_html($label); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>

    <div class="cat-site-footer__bar">
        <div class="cat-site-footer__bar-inner conteneur">
            <p class="cat-site-footer__copyright"><?php echo esc_html($copyright); ?></p>
            <?php if (!empty($legalLinks)) : ?>
                <ul class="cat-site-footer__legal">
                    <?php foreach ($legalLinks as $link) :
                        $label = (string) ($link['label'] ?? '');
                        $url = (string) ($link['url'] ?? '');
                        if ($label === '' || $url === '') {
                            continue;
                        }
                        ?>
                        <li>
                            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</footer>
