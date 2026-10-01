<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use ChassesAuTresor\Core\Support\CoreServiceFactory;
use Closure;

final class AdminAjaxHandler
{
    private const PAYMENTS_PER_PAGE = 20;

    private static ?Closure $paymentTableRenderer = null;
    private static ?Closure $huntCacheClearer = null;
    private static ?Closure $conversionServiceFactory = null;

    public static function register(callable $addAction): void
    {
        $actions = [
            'rechercher_utilisateur' => 'searchUsers',
            'lister_historique_paiements' => 'listPayments',
            'update_conversion_status' => 'updateConversionStatus',
            'recuperer_details_acf' => 'inspectAcf',
            'cta_reset_stats' => 'resetStatistics',
            'cta_toggle_site_protection' => 'toggleSiteProtection',
        ];
        foreach ($actions as $action => $method) {
            $addAction('wp_ajax_' . $action, [self::class, $method]);
        }
    }

    public static function configure(
        callable $paymentTableRenderer,
        callable $huntCacheClearer
    ): void
    {
        self::$paymentTableRenderer = Closure::fromCallable($paymentTableRenderer);
        self::$huntCacheClearer = Closure::fromCallable($huntCacheClearer);
    }

    public static function setConversionServiceFactory(callable $factory): void
    {
        self::$conversionServiceFactory = Closure::fromCallable($factory);
    }

    public static function searchUsers(): void
    {
        if (!self::isAdministrator(__('⛔ Accès refusé.', 'chassesautresor-com'), true)) {
            return;
        }
        $search = isset($_GET['term']) && is_scalar($_GET['term'])
            ? sanitize_text_field((string) $_GET['term']) : '';
        if (empty($search)) {
            wp_send_json_error(['message' => __('❌ Requête vide.', 'chassesautresor-com')]);
            return;
        }
        $users = get_users([
            'search' => '*' . esc_attr($search) . '*',
            'search_columns' => ['user_login', 'display_name', 'user_email'],
        ]);
        if (empty($users)) {
            wp_send_json_error(['message' => __('❌ Aucun utilisateur trouvé.', 'chassesautresor-com')]);
            return;
        }
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->ID,
                'text' => esc_html($user->display_name) . ' (' . esc_html($user->user_login) . ')',
            ];
        }
        wp_send_json_success($results);
    }

    public static function listPayments(): void
    {
        if (!self::isAdministrator()) {
            return;
        }
        if (self::$paymentTableRenderer === null) {
            wp_send_json_error(['message' => __('Service indisponible.', 'chassesautresor-com')]);
            return;
        }
        $page = max(1, isset($_POST['page']) ? (int) $_POST['page'] : 1);
        $service = self::conversionService();
        $requests = $service->getRequests(null, null, self::PAYMENTS_PER_PAGE, ($page - 1) * self::PAYMENTS_PER_PAGE);
        $pages = max(1, (int) ceil($service->countRequests() / self::PAYMENTS_PER_PAGE));
        $html = empty($requests)
            ? '<p>' . esc_html__('Aucune demande de paiement.', 'chassesautresor-com') . '</p>'
            : (string) (self::$paymentTableRenderer)($requests);
        if (!empty($requests)) {
            $html .= self::pagination($page, $pages);
        }
        wp_send_json_success(['html' => $html, 'page' => $page, 'pages' => $pages]);
    }

    public static function updateConversionStatus(): void
    {
        if (!self::isAdministrator()) {
            return;
        }
        $paymentId = isset($_POST['paiement_id']) ? (int) $_POST['paiement_id'] : 0;
        $status = isset($_POST['statut']) && is_scalar($_POST['statut'])
            ? sanitize_text_field((string) $_POST['statut']) : '';
        if ($paymentId <= 0 || !in_array($status, ['regle', 'annule', 'refuse'], true)) {
            wp_send_json_error();
            return;
        }
        $result = self::conversionService()->updateStatus($paymentId, $status, current_time('mysql'));
        if (!is_array($result)) {
            wp_send_json_error();
            return;
        }
        if (function_exists('cat_debug')) {
            cat_debug("✅ Statut mis à jour pour l'entrée {$paymentId} : {$result['status']}");
        }
        if ($result['paid_amount'] > 0) {
            $option = 'total_paiements_effectues_mensuel_' . date('Y_m');
            update_option($option, (float) get_option($option, 0) + $result['paid_amount']);
        }
        wp_send_json_success(['status' => $result['status']]);
    }

    public static function inspectAcf(): void
    {
        if (!self::isAdministrator(__('Non autorisé', 'chassesautresor-com'))) {
            return;
        }
        ob_start();
        foreach (self::acfGroupKeys() as $key) {
            acf_inspect_field_group($key);
            echo "\n";
        }
        wp_send_json_success(wp_strip_all_tags((string) ob_get_clean()));
    }

    public static function resetStatistics(): void
    {
        if (!self::isAdministrator(__('Non autorisé', 'chassesautresor-com'))) {
            return;
        }
        check_ajax_referer('cta_reset_stats', 'nonce');
        if (self::$huntCacheClearer === null) {
            wp_send_json_error(['message' => __('Service indisponible.', 'chassesautresor-com')]);
            return;
        }
        global $wpdb;
        $result = (new AdminStatisticsResetService($wpdb, self::$huntCacheClearer))->reset();
        if ($result['error'] !== '') {
            wp_send_json_error($result['error']);
            return;
        }
        wp_send_json_success(['deleted' => $result['deleted']]);
    }

    public static function toggleSiteProtection(): void
    {
        if (!self::isAdministrator(__('Non autorisé', 'chassesautresor-com'))) {
            return;
        }
        check_ajax_referer('cta_site_protection', 'nonce');
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? '1' : '0';
        update_option('ca_site_password_enabled', $enabled);
        wp_send_json_success(['enabled' => $enabled]);
    }

    private static function isAdministrator($message = null, bool $wrapped = false): bool
    {
        if (current_user_can('administrator')) {
            return true;
        }
        wp_send_json_error($wrapped ? ['message' => $message] : $message);
        return false;
    }

    private static function conversionService(): object
    {
        if (self::$conversionServiceFactory !== null) {
            return (self::$conversionServiceFactory)();
        }

        global $wpdb;

        return CoreServiceFactory::conversion($wpdb);
    }

    /** @return string[] */
    private static function acfGroupKeys(): array
    {
        return [
            'group_67b58c51b9a49',
            'group_67b58134d7647',
            'group_67c7dbfea4a39',
            'group_68a1fb240748a',
            'group_68abd01f80aee',
        ];
    }

    private static function pagination(int $page, int $pages): string
    {
        return '<div class="pager">'
            . '<button class="pager-first" aria-label="Première page"><i class="fa-solid fa-angles-left"></i></button>'
            . '<button class="pager-prev" aria-label="Page précédente"><i class="fa-solid fa-angle-left"></i></button>'
            . '<span class="pager-info">' . $page . ' / ' . $pages . '</span>'
            . '<button class="pager-next" aria-label="Page suivante"><i class="fa-solid fa-angle-right"></i></button>'
            . '<button class="pager-last" aria-label="Dernière page"><i class="fa-solid fa-angles-right"></i></button>'
            . '</div>';
    }
}
