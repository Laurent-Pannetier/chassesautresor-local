<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('current_user_can')) {
    function current_user_can($cap): bool
    {
        return true;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null): void
    {
        throw new Exception('error');
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null)
    {
        return $data;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        return $str;
    }
}

if (!function_exists('current_time')) {
    function current_time($type)
    {
        return '2023-01-01 00:00:00';
    }
}

if (!function_exists('update_user_points')) {
    function update_user_points($user_id, $points_change, $reason = '', $origin_type = 'admin', $origin_id = null): void
    {
        global $user_points, $last_origin_type;
        $user_points[$user_id] = ($user_points[$user_id] ?? 0) + $points_change;
        $last_origin_type     = $origin_type;
    }
}

class PointsRepository
{
    public function __construct($wpdb)
    {
    }

    public function updateRequestStatus(int $id, string $status, array $dates = []): void
    {
    }

    public function getRequestById(int $id): ?array
    {
        global $request_fixture;
        return $request_fixture;
    }
}

require_once __DIR__ . '/../wp-content/themes/chassesautresor/inc/admin-functions.php';

class AjaxUpdateRequestStatusTest extends TestCase
{
    /**
     * @return array<string[]>
     */
    public function statusProvider(): array
    {
        return [
            ['annule'],
            ['refuse'],
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function test_status_cancel_or_refuse_restores_balance(string $status): void
    {
        global $request_fixture, $user_points, $last_origin_type;
        $user_points      = [];
        $last_origin_type = null;
        $request_fixture = [
            'user_id' => 7,
            'points'  => -150,
        ];

        $_POST['paiement_id'] = 12;
        $_POST['statut'] = $status;

        ajax_update_request_status();

        $this->assertSame(150, $user_points[7]);
        $this->assertSame('admin', $last_origin_type);
    }
}
