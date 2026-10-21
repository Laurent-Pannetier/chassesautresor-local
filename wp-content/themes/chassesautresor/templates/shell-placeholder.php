<?php
/**
 * Empty page for a shell destination whose content is not defined.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

get_header();

$slug = isset($GLOBALS['cta_shell_page']) ? (string) $GLOBALS['cta_shell_page'] : '';
$titles = function_exists('cta_shell_page_titles') ? cta_shell_page_titles() : [];
$title = $titles[$slug] ?? '';
?>
<main class="app-placeholder" id="app-placeholder">
    <h1 class="app-placeholder__title"><?php echo esc_html($title); ?></h1>
    <p class="app-placeholder__note">
        <?php esc_html_e('Cette page est un emplacement. Son contenu n’est pas défini.', 'chassesautresor-com'); ?>
    </p>
</main>
<?php
get_footer();
