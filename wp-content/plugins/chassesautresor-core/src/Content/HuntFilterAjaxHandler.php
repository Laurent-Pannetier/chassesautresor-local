<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use Closure;

final class HuntFilterAjaxHandler
{
    private static ?Closure $renderer = null;

    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_ca_filter_chasses', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_ca_filter_chasses', [self::class, 'handle']);
    }

    public static function configure(callable $renderer): void
    {
        self::$renderer = Closure::fromCallable($renderer);
    }

    public static function handle(): void
    {
        check_ajax_referer('ca-filter-chasses', 'nonce');

        $request = is_array($_POST) ? wp_unslash($_POST) : [];
        $results = (new HuntFilterApplicationService())->filter(HuntFilterRequestService::normalize($request));

        if (!is_array($results) || !isset($results['ids']) || !is_array($results['ids'])) {
            wp_send_json_error([
                'message' => __('Impossible de charger les chasses.', 'chassesautresor-com'),
            ]);
            return;
        }

        $huntIds = array_map('intval', $results['ids']);
        $html = self::$renderer !== null
            ? (string) (self::$renderer)($huntIds)
            : (new HuntCardRenderer())->grid($huntIds, 'organisateur-chasses-grid');

        wp_send_json_success([
            'html' => $html,
            'total' => (int) ($results['total'] ?? count($huntIds)),
            'nonce' => wp_create_nonce('ca-filter-chasses'),
            'filters' => [
                'available' => is_array($results['available_filters'] ?? null)
                    ? $results['available_filters'] : [],
                'normalized' => is_array($results['filters_normalises'] ?? null)
                    ? $results['filters_normalises'] : [],
            ],
            'message' => is_string($results['message'] ?? null) ? $results['message'] : '',
        ]);
    }
}
