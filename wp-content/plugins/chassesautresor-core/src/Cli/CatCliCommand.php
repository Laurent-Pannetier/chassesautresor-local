<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Cli;

use ChassesAuTresor\Core\Messages\UserMessageRepository;

/**
 * WP-CLI commands for chassesautresor.
 */
class CatCliCommand
{
    /**
     * Migrate user and site messages to the user_messages table.
     *
     * ## EXAMPLES
     *
     *     wp cat migrate-messages
     */
    public function migrate_messages(): void
    {
        global $wpdb;

        $repository = new UserMessageRepository($wpdb);
        $userIds = get_users(['fields' => 'ids']);

        foreach ($userIds as $userId) {
            $migrated = $this->migrateUserMessages($repository, (int) $userId);

            if ($migrated > 0) {
                \WP_CLI::log(
                    sprintf(
                        /* translators: 1: number of messages, 2: user ID. */
                        __('%1$d messages migrés pour l\'utilisateur %2$d', 'chassesautresor-com'),
                        $migrated,
                        $userId
                    )
                );
            }
        }

        \WP_CLI::success(__('Migration des messages terminée.', 'chassesautresor-com'));
    }

    private function migrateUserMessages(UserMessageRepository $repository, int $userId): int
    {
        $migrated = 0;
        $persistent = get_user_meta($userId, '_myaccount_messages', true);

        if (is_array($persistent)) {
            foreach ($persistent as $key => $message) {
                if (!is_array($message)) {
                    continue;
                }

                $status = isset($message['status']) ? (string) $message['status'] : 'persistent';
                $expiresAt = isset($message['expires_at']) ? (string) $message['expires_at'] : null;
                $payload = $message;
                $payload['key'] = (string) $key;
                unset($payload['status'], $payload['expires_at']);

                $repository->insert($userId, wp_json_encode($payload), $status, $expiresAt);
                $migrated++;
            }

            delete_user_meta($userId, '_myaccount_messages');
        }

        $flash = get_user_meta($userId, '_myaccount_flash_messages', true);

        if (is_array($flash)) {
            foreach ($flash as $message) {
                if (!is_array($message)) {
                    continue;
                }

                $status = isset($message['status']) ? (string) $message['status'] : 'flash';
                $expiresAt = isset($message['expires_at']) ? (string) $message['expires_at'] : null;
                $payload = $message;
                unset($payload['status'], $payload['expires_at']);

                $repository->insert($userId, wp_json_encode($payload), $status, $expiresAt);
                $migrated++;
            }

            delete_user_meta($userId, '_myaccount_flash_messages');
        }

        return $migrated;
    }
}
