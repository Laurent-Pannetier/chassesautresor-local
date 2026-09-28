<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\SiteMessageService;
use ChassesAuTresor\Core\Messages\UserMessageRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Messages/UserMessageRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Messages/SiteMessageService.php';

class SiteMessageServiceTest extends TestCase
{
    public function testDeduplicateKeepsFirstKeyedMessageAndAllUnkeyedMessages(): void
    {
        $wpdb = (object) ['prefix' => 'wp_'];
        $service = new SiteMessageService(new UserMessageRepository($wpdb));
        $messages = [
            ['message_key' => 'shared', 'content' => 'first'],
            ['message_key' => 'shared', 'content' => 'second'],
            ['content' => 'unkeyed one'],
            ['content' => 'unkeyed two'],
        ];

        $this->assertSame(
            [$messages[0], $messages[2], $messages[3]],
            $service->deduplicate($messages)
        );
    }
}
