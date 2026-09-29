<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountMessageService;
use ChassesAuTresor\Core\Messages\UserMessageRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Messages/UserMessageRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Messages/AccountMessageService.php';

class AccountMessageServiceTest extends TestCase
{
    public function testServiceIsExposedFromCoreNamespace(): void
    {
        $wpdb = (object) ['prefix' => 'wp_'];
        $service = new AccountMessageService(new UserMessageRepository($wpdb));

        $this->assertInstanceOf(AccountMessageService::class, $service);
    }
}
