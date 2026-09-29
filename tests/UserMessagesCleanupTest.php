<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\UserMessagesCleanup;
use ChassesAuTresor\Core\Messages\UserMessageRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Messages/UserMessageRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Messages/UserMessagesCleanup.php';

class UserMessagesCleanupWpdb
{
    public string $prefix = 'wp_';

    public string $query = '';

    public function query(string $query): void
    {
        $this->query = $query;
    }
}

class UserMessagesCleanupTest extends TestCase
{
    protected $backupGlobals = false;

    public function testRunPurgesExpiredMessages(): void
    {
        global $wpdb;

        $wpdb = new UserMessagesCleanupWpdb();

        UserMessagesCleanup::run();

        $this->assertSame(
            'DELETE FROM wp_user_messages WHERE expires_at IS NOT NULL AND expires_at < NOW()',
            $wpdb->query
        );
    }

    public function testCleanupUsesDedicatedCronHook(): void
    {
        $this->assertSame(
            'chassesautresor_core_purge_expired_messages',
            UserMessagesCleanup::HOOK
        );
        $this->assertTrue(class_exists(UserMessageRepository::class));
    }
}
