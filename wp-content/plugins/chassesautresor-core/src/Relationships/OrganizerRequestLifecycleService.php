<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

final class OrganizerRequestLifecycleService
{
    public const TOKEN_META = 'organisateur_demande_token';
    public const DATE_META = 'organisateur_demande_date';
    public const LIFETIME = 2 * DAY_IN_SECONDS;

    private OrganizerRequestService $statusService;

    public function __construct(?OrganizerRequestService $statusService = null)
    {
        $this->statusService = $statusService ?? new OrganizerRequestService();
    }

    /** @return array{token:?string,expired:bool,expires_at?:int} */
    public function getStatus(int $userId): array
    {
        $token = (string) get_user_meta($userId, self::TOKEN_META, true);
        $date = $token !== '' ? get_user_meta($userId, self::DATE_META, true) : null;
        $timestamp = $date ? strtotime((string) $date) : false;
        $status = $this->statusService->getStatus(
            $token,
            $timestamp !== false ? $timestamp : null,
            $token !== '' ? (int) current_time('timestamp') : 0,
            self::LIFETIME
        );

        if ($status['clear']) {
            $this->clear($userId);
        }
        unset($status['clear']);

        return $status;
    }

    public function start(int $userId, callable $sendConfirmation): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $token = wp_generate_password(20, false);
        update_user_meta($userId, self::TOKEN_META, $token);
        update_user_meta($userId, self::DATE_META, current_time('mysql'));

        return (bool) $sendConfirmation($userId, $token);
    }

    public function resend(int $userId, callable $sendConfirmation): bool
    {
        $status = $this->getStatus($userId);
        if (empty($status['token'])) {
            return $this->start($userId, $sendConfirmation);
        }

        return (bool) $sendConfirmation($userId, (string) $status['token']);
    }

    public function confirm(int $userId, string $token, callable $createOrganizer, callable $promoteUser): ?int
    {
        $status = $this->getStatus($userId);
        if ($token === '' || empty($status['token']) || !hash_equals((string) $status['token'], $token)) {
            return null;
        }

        $this->clear($userId);
        $organizerId = (int) $createOrganizer($userId);
        if ($organizerId <= 0) {
            return null;
        }

        $promoteUser($userId);

        return $organizerId;
    }

    public function clear(int $userId): void
    {
        delete_user_meta($userId, self::TOKEN_META);
        delete_user_meta($userId, self::DATE_META);
    }
}
