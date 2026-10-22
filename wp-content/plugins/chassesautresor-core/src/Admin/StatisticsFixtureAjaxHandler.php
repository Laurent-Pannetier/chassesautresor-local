<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

/** Ajax endpoints for the demo statistics fixture tile. */
final class StatisticsFixtureAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cta_seed_statistics_fixtures', [self::class, 'seed']);
        $addAction('wp_ajax_cta_purge_statistics_fixtures', [self::class, 'purge']);
    }

    public static function seed(): void
    {
        if (!cat_user_can_seed_statistics()) {
            wp_send_json_error(__('Non autorisé', 'chassesautresor-com'));
            return;
        }

        check_ajax_referer('cta_stats_fixtures', 'nonce');
        $huntId = function_exists('cat_get_managed_hunt_id_for_user')
            ? (int) cat_get_managed_hunt_id_for_user()
            : 0;
        $result = StatisticsFixtureGateway::generate($huntId);
        self::respond($result, sprintf(
            /* translators: %d: number of created fixture players. */
            __('%d joueurs de test ont été créés.', 'chassesautresor-com'),
            (int) $result['users']
        ));
    }

    public static function purge(): void
    {
        if (!cat_user_can_seed_statistics()) {
            wp_send_json_error(__('Non autorisé', 'chassesautresor-com'));
            return;
        }

        check_ajax_referer('cta_stats_fixtures', 'nonce');
        $result = StatisticsFixtureGateway::purge();
        $message = (int) $result['users'] === 0
            ? __('Aucun joueur de test à retirer.', 'chassesautresor-com')
            : sprintf(
                /* translators: %d: number of removed fixture players. */
                __('%d joueurs de test ont été retirés.', 'chassesautresor-com'),
                (int) $result['users']
            );
        self::respond($result, $message);
    }

    /** @param array{users:int,rows:int,error:string} $result */
    private static function respond(array $result, string $success): void
    {
        if ($result['error'] === '') {
            wp_send_json_success([
                'message' => $success,
                'users' => (int) $result['users'],
            ]);
            return;
        }

        wp_send_json_error(self::errorMessage($result['error']));
    }

    private static function errorMessage(string $code): string
    {
        if ($code === 'empty_catalog') {
            return __('Cette chasse n’a pas encore d’énigme à remplir.', 'chassesautresor-com');
        }
        if ($code === 'missing_hunt') {
            return __('Aucune chasse à remplir.', 'chassesautresor-com');
        }

        return __('La mise à jour des joueurs de test a échoué.', 'chassesautresor-com');
    }
}
