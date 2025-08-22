<?php
/**
 * Chasses section for "Mon Compte".
 *
 * Displays recent hunts and attempt statistics for the current user.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id      = (int) $current_user->ID;

$dir = get_stylesheet_directory();
$uri = get_stylesheet_directory_uri();

wp_enqueue_script(
    'pager',
    $uri . '/assets/js/core/pager.js',
    [],
    filemtime($dir . '/assets/js/core/pager.js'),
    true
);

wp_enqueue_script(
    'tentatives-pager',
    $uri . '/assets/js/tentatives-pager.js',
    ['pager'],
    filemtime($dir . '/assets/js/tentatives-pager.js'),
    true
);


// Retrieve last 4 engaged hunts and their latest enigme
$chasse_ids  = [];
$enigme_map = [];
if ($user_id) {
    global $wpdb;
    $table      = $wpdb->prefix . 'engagements';
    $prepared   = $wpdb->prepare(
        "SELECT DISTINCT chasse_id FROM {$table} WHERE user_id = %d AND chasse_id IS NOT NULL ORDER BY date_engagement DESC LIMIT 4",
        $user_id
    );
    $chasse_ids = $wpdb->get_col($prepared);

    foreach ($chasse_ids as $cid) {
        $enigme_map[$cid] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT enigme_id FROM {$table} WHERE user_id = %d AND chasse_id = %d AND enigme_id IS NOT NULL ORDER BY date_engagement DESC LIMIT 1",
                $user_id,
                $cid
            )
        );
    }
}
?>
<div class="dashboard-grid stats-cards myaccount-chasses-cards">
    <?php foreach ($chasse_ids as $chasse_id) : ?>
    <?php $enigme_id = $enigme_map[$chasse_id] ?? 0; ?>
    <div class="dashboard-card">
        <?php afficher_picture_vignette_chasse($chasse_id); ?>
        <?php if ($enigme_id) : ?>
        <div class="enigme-thumbnail">
            <?php afficher_picture_vignette_enigme($enigme_id, get_the_title($enigme_id), ['thumbnail']); ?>
        </div>
        <?php endif; ?>
        <h3><?php echo esc_html(get_the_title($chasse_id)); ?></h3>
        <a class="stat-value" href="<?php echo esc_url(get_permalink($chasse_id)); ?>">
            <?php esc_html_e('Voir', 'chassesautresor-com'); ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php
// Tentative statistics for user
$pending = $total = $success = 0;
$tentatives = [];
$per_page   = 10;
$page       = max(1, (int) ($_GET['page'] ?? 1));
$offset     = ($page - 1) * $per_page;
if ($user_id) {
    $table = $wpdb->prefix . 'enigme_tentatives';
    $pending = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND resultat = 'attente' AND traitee = 0",
        $user_id
    ));
    $total = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
        $user_id
    ));
    $success = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND resultat = 'bon'",
        $user_id
    ));
    if ($total > 0) {
        $tentatives = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.post_title FROM {$table} t JOIN {$wpdb->posts} p ON t.enigme_id = p.ID WHERE t.user_id = %d ORDER BY t.date_tentative DESC LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ));
    }
}
$pages = (int) ceil($total / $per_page);
?>
<?php if ($total > 0) : ?>
    <h3><?php esc_html_e('Tentatives', 'chassesautresor-com'); ?></h3>
    <div class="table-header">
        <?php if ($pending > 0) : ?>
        <span class="stat-badge"><?php printf(esc_html(_n('%d tentative en attente', '%d tentatives en attente', $pending, 'chassesautresor-com')), $pending); ?></span>
        <?php endif; ?>
        <span class="stat-badge"><?php printf(esc_html(_n('%d tentative', '%d tentatives', $total, 'chassesautresor-com')), $total); ?></span>
        <?php if ($success > 0) : ?>
        <span class="stat-badge" style="color:var(--color-success);">
            <?php printf(esc_html(_n('%d bonne réponse', '%d bonnes réponses', $success, 'chassesautresor-com')), $success); ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="stats-table-wrapper" data-per-page="<?php echo esc_attr($per_page); ?>">
        <table class="stats-table tentatives-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'chassesautresor-com'); ?></th>
                    <th><?php esc_html_e('Énigme', 'chassesautresor-com'); ?></th>
                    <th><?php esc_html_e('Proposition', 'chassesautresor-com'); ?></th>
                    <th><?php esc_html_e('Résultat', 'chassesautresor-com'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tentatives as $tent) : ?>
                <tr>
                    <td><?php echo esc_html(mysql2date('d/m/Y H:i', $tent->date_tentative)); ?></td>
                    <td><?php echo esc_html($tent->post_title); ?></td>
                    <?php echo cta_render_proposition_cell($tent->reponse_saisie ?? ''); ?>
                    <?php
                    $result = $tent->resultat;
                    $class  = 'etiquette-error';
                    if ($result === 'bon') {
                        $class = 'etiquette-success';
                    } elseif ($result === 'attente') {
                        $class = 'etiquette-pending';
                    }
                    ?>
                    <td>
                        <span class="etiquette <?php echo esc_attr($class); ?>">
                            <?php echo esc_html__($result, 'chassesautresor-com'); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php echo cta_render_pager($page, $pages, 'tentatives-pager'); ?>
    </div>
<?php endif; ?>
